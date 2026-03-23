<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Review;
use Illuminate\Http\UploadedFile;

class ReviewService
{

    /**
     * Khách hàng có thể review sản phẩm này không?
     */
    public function canReview(int $customerId, int $productId, int $orderId): array
    {
        // Kiểm tra đơn hàng thuộc về khách hàng và đã hoàn thành
        $order = Order::where('id', $orderId)
            ->where('customer_id', $customerId)
            ->where('status', 'completed')
            ->first();

        if (!$order) {
            return ['can' => false, 'message' => 'Đơn hàng không hợp lệ hoặc chưa hoàn thành.'];
        }

        // Kiểm tra sản phẩm có trong đơn hàng không
        $hasProduct = $order->items()->where('product_id', $productId)->exists();

        if (!$hasProduct) {
            return ['can' => false, 'message' => 'Sản phẩm này không có trong đơn hàng.'];
        }

        // Kiểm tra đã review chưa
        $alreadyReviewed = Review::where('customer_id', $customerId)
            ->where('product_id', $productId)
            ->exists();

        if ($alreadyReviewed) {
            return ['can' => false, 'message' => 'Bạn đã đánh giá sản phẩm này rồi.'];
        }

        return ['can' => true, 'message' => 'OK'];
    }

    /**
     * Tạo review mới
     */
    public function create(int $customerId, array $data): Review
    {
        $images = [];

        if (!empty($data['images'])) {
            foreach ($data['images'] as $file) {
                if ($file instanceof UploadedFile) {
                    $images[] = $file->store('reviews', 'public');
                }
            }
        }

        return Review::create([
            'customer_id' => $customerId,
            'product_id' => $data['product_id'],
            'order_id' => $data['order_id'],
            'rating' => $data['rating'],
            'title' => $data['title'] ?? null,
            'comment' => $data['comment'] ?? null,
            'images' => $images ?: null,
            'is_verified_purchase' => true, // đã check order ở canReview()
            'is_approved' => false, // chờ admin duyệt
        ]);
    }

    /**
     * Cập nhật review (chỉ cho phép trước khi được duyệt)
     */
    public function update(Review $review, array $data): Review
    {
        if ($review->is_approved) {
            throw new \Exception('Không thể chỉnh sửa đánh giá đã được duyệt.');
        }

        $review->update([
            'rating' => $data['rating'] ?? $review->rating,
            'title' => $data['title'] ?? $review->title,
            'comment' => $data['comment'] ?? $review->comment,
        ]);

        return $review->fresh();
    }

    /**
     * Thống kê rating của sản phẩm
     */
    public function getStats(int $productId): array
    {
        $reviews = Review::approved()->where('product_id', $productId);

        $total = $reviews->count();

        if ($total === 0) {
            return ['average' => 0, 'total' => 0, 'breakdown' => []];
        }

        $breakdown = $reviews->clone()
            ->selectRaw('rating, COUNT(*) as count')
            ->groupBy('rating')
            ->pluck('count', 'rating')
            ->toArray();

        return [
            'average' => round($reviews->avg('rating'), 1),
            'total' => $total,
            'breakdown' => array_map(
                fn($star) => [
                    'star' => $star,
                    'count' => $breakdown[$star] ?? 0,
                    'percentage' => round((($breakdown[$star] ?? 0) / $total) * 100),
                ],
                [5, 4, 3, 2, 1]
            ),
        ];
    }
}
