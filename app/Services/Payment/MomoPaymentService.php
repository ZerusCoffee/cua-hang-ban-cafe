<?php

namespace App\Services\Payment;

use App\Models\Coupon;
use App\Models\Order;
use App\Services\CartService;
use App\Services\CouponService;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MomoPaymentService implements PaymentServiceInterface
{
    private string $endpoint;
    private string $partnerCode;
    private string $accessKey;
    private string $secretKey;

    public function __construct(private OrderService $orderService, private CartService $cartService)
    {
        $this->endpoint = config('payment.momo.environment') === 'production'
            ? 'https://payment.momo.vn/v2/gateway/api/create'
            : 'https://test-payment.momo.vn/v2/gateway/api/create';
        $this->partnerCode = config('payment.momo.partner_code');
        $this->accessKey = config('payment.momo.access_key');
        $this->secretKey = config('payment.momo.secret_key');
    }

    public function handle(Order $order, array $data): JsonResponse
    {
        $requestId = uniqid();
        $amount = (int)$order->total;
        $rawHash = "accessKey={$this->accessKey}&amount={$amount}&extraData=&ipnUrl=" . route('momo.ipn')
            . "&orderId={$order->order_number}&orderInfo=Thanh toan don hang {$order->order_number}"
            . "&partnerCode={$this->partnerCode}&redirectUrl=" . env("PUBLIC_CLIENT_URL") . '/payment/momo/callback'
            . "&requestId={$requestId}&requestType=payWithATM";

        $signature = hash_hmac('sha256', $rawHash, $this->secretKey);

        $response = Http::withOptions([
            'verify' => false,
        ])->post($this->endpoint, [
            'partnerCode' => $this->partnerCode,
            'requestId' => $requestId,
            'amount' => $amount,
            'orderId' => $order->order_number,
            'orderInfo' => 'Thanh toan don hang ' . $order->order_number,
            'redirectUrl' => env("PUBLIC_CLIENT_URL") . '/payment/momo/callback',
            'ipnUrl' => route('momo.ipn'),
            'lang' => 'vi',
            'extraData' => '',
            'requestType' => 'payWithATM',
            'signature' => $signature,
        ]);

        $result = $response->json();

        if (!isset($result['payUrl'])) {
            return response()->json([
                'status' => 'error',
                'message' => $result['message'] ?? 'Không thể tạo thanh toán MOMO',
            ], 400);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Chuyển hướng đến MOMO',
            'data' => [
                'order_number' => $order->order_number,
                'payment_url' => $result['payUrl'],
                'qr_code' => $result['qrCodeUrl'] ?? null,
                'total' => $order->total,
            ],
        ]);
    }

    public function callback(Request $request): JsonResponse
    {
        // Callback để frontend redirect về, không xử lý order ở đây
        $orderNumber = $request->input('orderId');
        $resultCode = $request->input('resultCode');

        $order = Order::where('order_number', $orderNumber)->firstOrFail();

        return response()->json([
            'status' => $resultCode == '0' ? 'success' : 'error',
            'data' => [
                'order_number' => $order->order_number,
                'payment_status' => $order->payment_status,
            ],
        ]);
    }

    public function ipn(Request $request): JsonResponse
    {
        Log::info('MOMO IPN', $request->all());

        if (!$this->verifySignature($request->all())) {
            return response()->json(['status' => 'error', 'message' => 'Invalid signature'], 400);
        }

        $orderNumber = $request->input('orderId');
        $resultCode = $request->input('resultCode');
        $transId = $request->input('transId');

        $order = Order::where('order_number', $orderNumber)->firstOrFail();

        if ($resultCode == '0') {
            $this->orderService->markPaid($order, $transId, $request->all());
            $order->updateStatus('pending', 'Thanh toán MOMO thành công');

            if ($order->coupon_id) {
                $coupon = Coupon::find($order->coupon_id);
                if ($coupon) {
                    try {
                        app(CouponService::class)->apply(
                            $coupon,
                            $order->customer_id,
                            $order->id,
                            $order->subtotal
                        );
                    } catch (\Exception $e) {
                        Log::error('Lỗi áp dụng coupon PayPal: ' . $e->getMessage(), [
                            'order' => $order->order_number,
                            'coupon_id' => $coupon->id,
                        ]);
                    }
                }
            }
        } else {
            $order->update(['payment_status' => 'failed']);
        }

        $this->cartService->clear($order->customer_id);

        return response()->json(['status' => 'success']);
    }

    private function verifySignature(array $data): bool
    {
        $rawHash = "accessKey={$this->accessKey}"
            . "&amount={$data['amount']}&extraData=" . ($data['extraData'] ?? '')
            . "&message={$data['message']}&orderId={$data['orderId']}"
            . "&orderInfo={$data['orderInfo']}&orderType={$data['orderType']}"
            . "&partnerCode={$data['partnerCode']}&payType={$data['payType']}"
            . "&requestId={$data['requestId']}&responseTime={$data['responseTime']}"
            . "&resultCode={$data['resultCode']}&transId={$data['transId']}";

        return hash_hmac('sha256', $rawHash, $this->secretKey) === ($data['signature'] ?? '');
    }

    public function retry(Order $order): JsonResponse
    {
        // TODO: Implement retry() method.
    }
}
