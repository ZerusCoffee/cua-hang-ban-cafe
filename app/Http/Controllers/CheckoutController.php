<?php

namespace App\Http\Controllers;

use App\Events\OrderCreated;
use App\Http\Requests\CheckoutRequest;
use App\Jobs\CancelExpiredOrders;
use App\Models\Coupon;
use App\Services\CartService;
use App\Services\CouponService;
use App\Services\OrderService;
use App\Services\Payment\CodPaymentService;
use App\Services\Payment\MomoPaymentService;
use App\Services\Payment\PaypalPaymentService;
use App\Services\Payment\VnpayPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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

        try {
            $order = $this->orderService->createFromCart(
                cart: $cart,
                customerId: $userId,
                data: $request->validated(),
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }

        CancelExpiredOrders::dispatch($order->id)
            ->delay(now()->addMinutes(20)); // tự động hủy sau 20p nếu k xác nhận

        event(new OrderCreated($order)); //notification server

        if ($order->coupon_id && $request->payment_method === 'cod') {
            $coupon = Coupon::find($order->coupon_id);
            if ($coupon) {
                try {
                    app(CouponService::class)->apply(
                        $coupon,
                        $userId,
                        $order->id,
                        $order->discount_amount
                    );
                } catch (\Exception $e) {
                    Log::info("Lỗi áp dụng mã giảm giá: " . $e->getMessage());
                }
            }
        }

        // Delegate sang payment service tương ứng
        $response = $this->resolvePaymentService($request->payment_method)
            ->handle($order, $request->validated());

        if ($request->payment_method === 'cod') {
            $this->cartService->clear($userId);
        }

        return $response;
    }

    // ─── Payment callbacks ────────────────────────────────────────────────────

    public function vnpayCallback(Request $request): JsonResponse
    {
        return app(VnpayPaymentService::class)->callback($request);
    }

    public function vnpayIpn(Request $request): JsonResponse
    {
        return app(VnpayPaymentService::class)->ipn($request);
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

    /**
     * Tạo đơn hàng + PayPal order ID (cho popup)
     */
    public function createPaypalOrder(Request $request): JsonResponse
    {
        $userId = $request->user()->id;
        $cart = $this->cartService->get($userId);

        if (empty($cart)) {
            return $this->errorResponse('Giỏ hàng trống', 422);
        }

        $stockErrors = $this->cartService->checkStock($userId);
        if (!empty($stockErrors)) {
            return $this->errorResponse('Không đủ nguyên liệu', 422, ['stock_errors' => $stockErrors]);
        }

        $data = $request->only([
            'shipping_full_name',
            'shipping_phone',
            'shipping_province',
            'shipping_ward',
            'shipping_address_details',
            'customer_notes',
            'payment_method',
            'coupon_code',
        ]);

        try {
            $order = $this->orderService->createFromCart(
                cart: $cart,
                customerId: $userId,
                data: $data,
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }

        CancelExpiredOrders::dispatch($order->id)->delay(now()->addMinutes(20));
        event(new OrderCreated($order));

        return app(PaypalPaymentService::class)->handle($order, $request->all());
    }

    /**
     * Capture PayPal order sau khi người dùng approve
     */
    public function capturePaypalOrder(Request $request): JsonResponse
    {
        return app(PaypalPaymentService::class)->captureOrder($request);
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
