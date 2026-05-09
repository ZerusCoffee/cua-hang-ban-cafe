<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use App\Services\CartService;
use App\Services\CouponService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CouponController extends Controller
{

    public function __construct(protected CouponService $couponService, protected CartService $cartService)
    {
    }

    /**
     * GET /api/coupon
     * Danh sách coupon đang có hiệu lực (hiển thị cho khách chọn)
     */
    public function index(Request $request): JsonResponse
    {
        $customerId = $request->user()->id;

        $coupons = Coupon::valid()
            ->get()
            ->filter(fn($coupon) => $coupon->canBeUsedByCustomer($customerId))
            ->map(fn($coupon) => [
                'code' => $coupon->code,
                'name' => $coupon->name,
                'description' => $coupon->description,
                'type_label' => $coupon->type_label,
                'minimum_order_amount' => $coupon->minimum_order_amount,
                'maximum_discount_amount' => $coupon->maximum_discount_amount,
                'expires_at' => $coupon->expires_at?->format('d/m/Y H:i'),
            ])
            ->values();

        return $this->successResponse($coupons);
    }

    /**
     * POST /api/coupon/preview
     * Xem trước giảm giá dựa trên giỏ hàng thực tế của khách hàng
     */
    public function preview(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required|string',
        ]);

        $userId = $request->user()->id;
        $cartSummary = $this->cartService->summary($userId);
        $orderAmount = $cartSummary['subtotal'] ?? 0;

        if ($orderAmount <= 0) {
            return $this->errorResponse('Giỏ hàng của bạn đang trống', 422);
        }

        $result = $this->couponService->validate(
            code: $request->code,
            customerId: $userId,
            orderAmount: $orderAmount,
        );

        if (!$result['valid']) {
            return $this->errorResponse($result['message'], 422);
        }

        return $this->successResponse([
            'discount_amount' => $result['discount_amount'],
            'coupon' => [
                'code' => $result['coupon']->code,
                'name' => $result['coupon']->name,
                'type' => $result['coupon']->type,
            ],
            'subtotal' => $orderAmount,
            'total_after_discount' => $orderAmount - $result['discount_amount'],
        ], $result['message']);
    }
}
