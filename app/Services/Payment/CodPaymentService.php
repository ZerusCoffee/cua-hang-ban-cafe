<?php

namespace App\Services\Payment;

use App\Models\Order;
use App\Services\CartService;
use App\Services\OrderService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CodPaymentService
{
    use ApiResponse;

    public function __construct(
        private OrderService $orderService,
        private CartService $cartService
    ) {}

    public function handle(Order $order, array $data): JsonResponse
    {
        try {
            $this->cartService->clear($order->customer_id);
            Log::info('Đã xóa cart COD', [
                'user_id' => $order->customer_id,
                'order' => $order->order_number
            ]);
        } catch (\Exception $e) {
            Log::error('Lỗi xóa cart COD: ' . $e->getMessage());
        }

        return $this->successResponse([
            'order_number' => $order->order_number,
            'total'        => $order->total,
            'status'       => $order->status,
            'payment_status' => $order->payment_status
        ], 'Đặt hàng COD thành công', 201);
    }
}
