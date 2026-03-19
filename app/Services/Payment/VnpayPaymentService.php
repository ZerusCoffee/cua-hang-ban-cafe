<?php

namespace App\Services\Payment;

use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class VnpayPaymentService implements PaymentServiceInterface
{
    public function __construct(private OrderService $orderService)
    {
    }

    public function handle(Order $order, array $data): JsonResponse
    {
        $params = [
            'vnp_TxnRef' => $order->order_number,
            'vnp_Amount' => $order->total * 100,
            'vnp_Locale' => 'vn',
            'vnp_IpAddr' => request()->ip(),
            'vnp_ReturnUrl' => env("PUBLIC_CLIENT_URL") . '/payment/vnpay/callback',
            'vnp_CreateDate' => now()->format('YmdHis'),
            'vnp_CurrCode' => 'VND',
            'vnp_Command' => 'pay',
            'vnp_Version' => '2.1.0',
            'vnp_TmnCode' => config('payment.vnpay.tmn_code'),
            'vnp_OrderInfo' => 'Thanh toan don hang ' . $order->order_number,
            'vnp_OrderType' => 'other',
        ];

        ksort($params);
        $query = http_build_query($params);
        $signature = hash_hmac('sha512', $query, config('payment.vnpay.hash_secret'));
        $paymentUrl = config('payment.vnpay.url') . '?' . $query . '&vnp_SecureHash=' . $signature;

        return response()->json([
            'status' => 'success',
            'message' => 'Chuyển hướng đến VNPAY',
            'data' => [
                'order_number' => $order->order_number,
                'payment_url' => $paymentUrl,
                'total' => $order->total,
            ],
        ]);
    }

    public function callback(Request $request): JsonResponse
    {
        Log::info("Callback vnpay: ", $request->all());
        $responseCode = $request->input('vnp_ResponseCode');
        $orderNumber = $request->input('vnp_TxnRef');

        $order = Order::where('order_number', $orderNumber)->firstOrFail();

        if ($responseCode === '00') {
            return response()->json([
                'status' => 'success',
                'message' => 'Thanh toán thành công',
                'data' => ['order_number' => $orderNumber],
            ]);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'Thanh toán thất bại',
        ], 400);
    }

    public function ipn(Request $request)
    {
        $inputData = $request->all();

        Log::info("VNPAY IPN:", $inputData);

        // 1. Lấy và tách secure hash ra khỏi params
        $vnpSecureHash = $inputData['vnp_SecureHash'] ?? '';
        unset($inputData['vnp_SecureHash'], $inputData['vnp_SecureHashType']);

        // 2. Verify signature
        ksort($inputData);
        $query = http_build_query($inputData);
        $expectedHash = hash_hmac('sha512', $query, config('payment.vnpay.hash_secret'));

        if (!hash_equals($expectedHash, $vnpSecureHash)) {
            Log::warning('VNPAY IPN: Invalid signature', $inputData);
            return response('{"RspCode":"97","Message":"Invalid signature"}', 200)
                ->header('Content-Type', 'application/json');
        }

        // 3. Tìm đơn hàng
        $orderNumber = $inputData['vnp_TxnRef'];
        $order = Order::where('order_number', $orderNumber)->first();

        if (!$order) {
            return response('{"RspCode":"01","Message":"Order not found"}', 200)
                ->header('Content-Type', 'application/json');
        }

        // 4. Kiểm tra số tiền
        $vnpAmount = (int)$inputData['vnp_Amount'];
        if ($vnpAmount !== (int)($order->total * 100)) {
            Log::warning('VNPAY IPN: Amount mismatch', [
                'order' => $orderNumber,
                'expected' => $order->total * 100,
                'received' => $vnpAmount,
            ]);
            return response('{"RspCode":"04","Message":"Invalid amount"}', 200)
                ->header('Content-Type', 'application/json');
        }

        // 5. Idempotency — tránh xử lý 2 lần
        if ($order->payment_status === 'paid') {
            return response('{"RspCode":"02","Message":"Order already confirmed"}', 200)
                ->header('Content-Type', 'application/json');
        }

        // 6. Xử lý kết quả
        $responseCode = $inputData['vnp_ResponseCode'];
        $transId = $inputData['vnp_TransactionNo'];

        if ($responseCode === '00') {
            $this->orderService->markPaid($order, $transId, $inputData);
            $order->updateStatus('pending', 'Thanh toán VNPAY thành công qua IPN');
        } else {
            $order->update(['payment_status' => 'failed']);
            Log::info('VNPAY IPN: Payment failed', [
                'order' => $orderNumber,
                'code' => $responseCode,
            ]);
        }

        // 7. VNPAY bắt buộc response đúng format này
        return response('{"RspCode":"00","Message":"Confirm success"}', 200)
            ->header('Content-Type', 'application/json');
    }
}
