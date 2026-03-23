<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Services\ReviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{

    public function __construct(protected ReviewService $reviewService)
    {
    }

    /**
     * Danh sách review đã duyệt của sản phẩm
     */
    public function index(int $productId): JsonResponse
    {
        $reviews = Review::approved()
            ->where('product_id', $productId)
            ->with('customer:id,name')
            ->latest()
            ->paginate(10);

        $stats = $this->reviewService->getStats($productId);

        return $this->successResponse([
            'stats' => $stats,
            'reviews' => $reviews,
        ]);
    }

    /**
     * Khách hàng tạo review
     */
    public function store(Request $request, int $productId): JsonResponse
    {
        $request->validate([
            'order_id' => 'required|integer|exists:orders,id',
            'rating' => 'required|integer|min:1|max:5',
            'title' => 'nullable|string|max:255',
            'comment' => 'nullable|string|max:2000',
            'images' => 'nullable|array|max:5',
            'images.*' => 'image|max:2048',
        ]);

        $customerId = $request->user()->id;

        $check = $this->reviewService->canReview($customerId, $productId, $request->order_id);

        if (!$check['can']) {
            return $this->errorResponse($check['message'], 422);
        }

        $review = $this->reviewService->create($customerId, array_merge(
            $request->only('order_id', 'rating', 'title', 'comment', 'images'),
            ['product_id' => $productId],
        ));

        return $this->successResponse($review, 'Đánh giá của bạn đã được gửi, chờ duyệt.', 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

     /**
     * Khách hàng cập nhật review của chính mình
     */
    public function update(Request $request, Review $review): JsonResponse
    {
         if ($review->customer_id !== $request->user()->id) {
            return $this->errorResponse('Bạn không có quyền chỉnh sửa đánh giá này.', 403);
        }

        $request->validate([
            'rating'  => 'sometimes|integer|min:1|max:5',
            'title'   => 'nullable|string|max:255',
            'comment' => 'nullable|string|max:2000',
        ]);

        try {
            $review = $this->reviewService->update($review, $request->only('rating', 'title', 'comment'));
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }

        return $this->successResponse($review, 'Cập nhật đánh giá thành công.');
    }

      /**
     * Khách hàng xoá review của chính mình
     */
      public function destroy(Request $request, Review $review): JsonResponse
    {
        if ($review->customer_id !== $request->user()->id) {
            return $this->errorResponse('Bạn không có quyền xoá đánh giá này.', 403);
        }

        $review->delete();

        return $this->successResponse(null, 'Xoá đánh giá thành công.');
    }
}
