<?php

namespace App\Services\Payment;

use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CodPaymentService implements PaymentServiceInterface
{
    public function __construct(private OrderService $orderService) {}

    public function handle(Order $order, array $data): JsonResponse
    {
        // COD không cần redirect, confirm status luôn
        $order->updateStatus('confirmed', 'Đơn hàng COD đã được xác nhận');

        return response()->json([
            'status'  => 'success',
            'message' => 'Đặt hàng thành công',
            'data'    => [
                'order_number' => $order->order_number,
                'total'        => $order->total,
                'status'       => $order->status,
            ],
        ], 201);
    }

    public function callback(Request $request): JsonResponse
    {
        // COD không có callback
        return response()->json(['status' => 'success']);
    }
}
