<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(
        private OrderService $orderService,
    )
    {
    }

    /**
     * GET /api/v1/order
     * Danh sách đơn hàng của customer đang login
     */
    public function index(Request $request): JsonResponse
    {
        $orders = Order::where('customer_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return $this->successResponse($orders, 'Lấy danh sách đơn hàng thành công');
    }

    /**
     * GET /api/v1/order/{orderNumber}
     * Chi tiết đơn hàng — chỉ xem được đơn của chính mình
     */
    public function show(Request $request, string $orderNumber): JsonResponse
    {
        $order = Order::where('order_number', $orderNumber)
            ->where('customer_id', $request->user()->id)
            ->with(['items'])
            ->firstOrFail();

        return $this->successResponse($order, 'Lấy chi tiết đơn hàng thành công');
    }

    /**
     * DELETE /api/v1/order/cancel/{orderNumber}
     */
    public function cancel(string $orderNumber): JsonResponse
    {
        $this->orderService->cancel($orderNumber);

        return $this->successResponse(null, 'Đã huỷ đơn hàng');
    }
}
