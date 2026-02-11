<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $query = Coupon::query();

            // Lọc theo trạng thái
            if ($request->has("status")) {
                switch ($request->status) {
                    case "active":
                        $query->active();
                        break;
                    case "expired":
                        $query->where("expires_at", "<", now());
                        break;
                    case "upcoming":
                        $query->where("starts_at", ">", now());
                        break;
                    case "inactive":
                        $query->where("is_active", false);
                        break;
                    case "valid":
                        $query->valid();
                        break;
                }
            }

            // Lọc theo type
            if ($request->has("type")) {
                $query->where("type", $request->type);
            }

            // Tìm kiếm theo code hoặc name
            if ($request->has("search")) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where("code", "LIKE", "%{$search}%")->orWhere(
                        "name",
                        "LIKE",
                        "%{$search}%",
                    );
                });
            }

            // Sắp xếp
            $sortBy = $request->get("sort_by", "created_at");
            $sortOrder = $request->get("sort_order", "desc");
            $query->orderBy($sortBy, $sortOrder);

            // Phân trang
            $perPage = $request->get("per_page", 15);
            $coupons = $query->paginate($perPage);

            // Thêm thông tin bổ sung
            $coupons->getCollection()->transform(function ($coupon) {
                $coupon->used_count = $coupon->usages()->count();
                $coupon->remaining_uses = $coupon->getRemainingUsesAttribute();
                return $coupon;
            });

            return $this->successResponse(
                $coupons,
                "Lấy danh sách coupon thành công",
            );
        } catch (\Exception $e) {
            return $this->errorResponse(
                "Có lỗi xảy ra khi lấy danh sách coupon: " . $e->getMessage(),
                500,
            );
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
