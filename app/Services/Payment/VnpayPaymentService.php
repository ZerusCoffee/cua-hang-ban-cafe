<?php

namespace App\Services\Payment;

use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VnpayPaymentService implements PaymentServiceInterface
{
    public function __construct(private OrderService $orderService) {}

    public function handle(Order $order, array $data): JsonResponse
    {
        $params = [
            'vnp_TxnRef'   => $order->order_number,
            'vnp_Amount'   => $order->total * 100,
            'vnp_Locale'   => 'vn',
            'vnp_IpAddr'   => request()->ip(),
            'vnp_ReturnUrl'=> route('vnpay.callback'),
            'vnp_CreateDate'=> now()->format('YmdHis'),
            'vnp_CurrCode' => 'VND',
            'vnp_Command'  => 'pay',
            'vnp_Version'  => '2.1.0',
            'vnp_TmnCode'  => config('payment.vnpay.tmn_code'),
            'vnp_OrderInfo'=> 'Thanh toan don hang ' . $order->order_number,
            'vnp_OrderType'=> 'other',
        ];

        ksort($params);
        $query     = http_build_query($params);
        $signature = hash_hmac('sha512', $query, config('payment.vnpay.hash_secret'));
        $paymentUrl = config('payment.vnpay.url') . '?' . $query . '&vnp_SecureHash=' . $signature;

        return response()->json([
            'status'  => 'success',
            'message' => 'Chuyển hướng đến VNPAY',
            'data'    => [
                'order_number' => $order->order_number,
                'payment_url'  => $paymentUrl,
                'total'        => $order->total,
            ],
        ]);
    }

    public function callback(Request $request): JsonResponse
    {
        // TODO: Verify signature + update order status
        $responseCode = $request->input('vnp_ResponseCode');
        $orderNumber  = $request->input('vnp_TxnRef');
        $transId      = $request->input('vnp_TransactionNo');

        $order = Order::where('order_number', $orderNumber)->firstOrFail();

        if ($responseCode === '00') {
            $this->orderService->markPaid($order, $transId, $request->all());
            $order->updateStatus('confirmed', 'Thanh toán VNPAY thành công');

            return response()->json(['status' => 'success', 'message' => 'Thanh toán thành công']);
        }

        $order->update(['payment_status' => 'failed']);

        return response()->json(['status' => 'error', 'message' => 'Thanh toán thất bại'], 400);
    }
}
