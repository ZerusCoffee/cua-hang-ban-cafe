<?php

namespace App\Http\Controllers;

use App\Http\Requests\CheckoutRequest;
use App\Services\CartService;
use App\Services\OrderService;
use App\Services\Payment\CodPaymentService;
use App\Services\Payment\MomoPaymentService;
use App\Services\Payment\PaypalPaymentService;
use App\Services\Payment\VnpayPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CheckoutController extends Controller
{
    public function __construct(
        private CartService  $cartService,
        private OrderService $orderService,
    )
    {
    }

    /**
     * POST /api/v1/checkout
     */
    public function checkout(CheckoutRequest $request): JsonResponse
    {
        $userId = $request->user()->id;
        $cart = $this->cartService->get($userId);

        if (empty($cart)) {
            return $this->errorResponse('Giỏ hàng trống', 422);
        }

        $stockErrors = $this->cartService->checkStock($userId);
        if (!empty($stockErrors)) {
            return $this->errorResponse('Không đủ nguyên liệu', 422, [
                'stock_errors' => $stockErrors,
            ]);
        }

        // Tạo order với status pending
        $order = $this->orderService->createFromCart(
            cart: $cart,
            customerId: $userId,
            data: $request->validated(),
        );

        // Delegate sang payment service tương ứng
        $response = $this->resolvePaymentService($request->payment_method)
            ->handle($order, $request->validated());

        if ($request->payment_method === 'cod') {
            $this->cartService->clear($userId);
        }

        return $response;
    }

    /**
     * GET /api/v1/order/checkout/cancel/{orderNumber}
     */
    public function cancel(string $orderNumber): JsonResponse
    {
        $this->orderService->cancel($orderNumber);

        return $this->successResponse(null, 'Đã huỷ đơn hàng');
    }

    // ─── Payment callbacks ────────────────────────────────────────────────────

    public function vnpayCallback(Request $request): JsonResponse
    {
        return app(VnpayPaymentService::class)->callback($request);
    }

    public function vnpayIpn(Request $request): JsonResponse
    {
        return app();
    }

    public function momoCallback(Request $request): JsonResponse
    {
        return app(MomoPaymentService::class)->callback($request);
    }

    public function momoIpn(Request $request): JsonResponse
    {
        return app(MomoPaymentService::class)->ipn($request);
    }

    public function paypalCallback(Request $request): JsonResponse
    {
        return app(PaypalPaymentService::class)->callback($request);
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function resolvePaymentService(string $method): mixed
    {
        return match ($method) {
            'cod' => app(CodPaymentService::class),
            'vnpay' => app(VnpayPaymentService::class),
            'momo' => app(MomoPaymentService::class),
            'paypal' => app(PaypalPaymentService::class),
        };
    }
}
