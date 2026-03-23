<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use App\Services\CouponService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CouponController extends Controller
{

    public function __construct(protected CouponService $couponService) {}

    /**
     * GET /api/coupons
     * Danh sách coupon đang có hiệu lực (hiển thị cho khách chọn)
     */
    public function index(Request $request): JsonResponse
    {
        $customerId = $request->user()->id;

        $coupons = Coupon::valid()
            ->get()
            ->filter(fn($coupon) => $coupon->canBeUsedByCustomer($customerId))
            ->map(fn($coupon) => [
                'code'                    => $coupon->code,
                'name'                    => $coupon->name,
                'description'             => $coupon->description,
                'type_label'              => $coupon->type_label,
                'minimum_order_amount'    => $coupon->minimum_order_amount,
                'maximum_discount_amount' => $coupon->maximum_discount_amount,
                'expires_at'              => $coupon->expires_at?->format('d/m/Y H:i'),
            ])
            ->values();

        return $this->successResponse($coupons);
    }

    /**
     * POST /api/coupons/validate
     * Kiểm tra coupon có hợp lệ không (dùng trước khi đặt hàng)
     */
    public function validate(Request $request): JsonResponse
    {
        $request->validate([
            'code'         => 'required|string',
            'order_amount' => 'required|numeric|min:0',
        ]);

        $result = $this->couponService->validate(
            code:        $request->code,
            customerId:  $request->user()->id,
            orderAmount: $request->order_amount,
        );

        if (!$result['valid']) {
            return $this->errorResponse($result['message'], 422);
        }

        return $this->successResponse([
            'discount_amount' => $result['discount_amount'],
            'coupon'          => [
                'code' => $result['coupon']->code,
                'name' => $result['coupon']->name,
                'type' => $result['coupon']->type,
            ],
        ], $result['message']);
    }

    /**
     * POST /api/coupons/apply
     * Apply coupon vào order sau khi order đã được tạo
     */
    public function apply(Request $request): JsonResponse
    {
        $request->validate([
            'code'         => 'required|string',
            'order_id'     => 'required|integer|exists:orders,id',
            'order_amount' => 'required|numeric|min:0',
        ]);

        $result = $this->couponService->validateAndApply(
            code:        $request->code,
            customerId:  $request->user()->id,
            orderId:     $request->order_id,
            orderAmount: $request->order_amount,
        );

        if (!$result['valid']) {
            return $this->errorResponse($result['message'], 422);
        }

        return $this->successResponse([
            'discount_amount' => $result['discount_amount'],
        ], $result['message']);
    }
}
