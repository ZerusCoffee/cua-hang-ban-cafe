<?php
// app/Http/Controllers/CartController.php

namespace App\Http\Controllers;

use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CartController extends Controller
{
    public function __construct(private CartService $cartService) {}

    /**
     * Lấy giỏ hàng hiện tại
     */
    public function index(): JsonResponse
    {
        $userId = Auth::id();
        $summary = $this->cartService->summary($userId);

        return $this->successResponse($summary, 'Lấy giỏ hàng thành công');
    }

    /**
     * Thêm sản phẩm vào giỏ hàng
     */
    public function addItem(Request $request): JsonResponse
    {
        $data = $request->validate([
            'product_id'                  => 'required|integer|exists:products,id',
            'quantity'                    => 'required|integer|min:1',
            'options'                     => 'nullable|array',
            'options.*.product_option_id' => 'required|integer|exists:product_options,id',
            'options.*.option_id'         => 'required|integer|exists:options,id',
            'options.*.group_name'        => 'required|string',
            'options.*.option_value'      => 'required|string',
            'options.*.additional_price'  => 'required|numeric|min:0',
        ]);

        $userId = Auth::id();

        $item        = $this->cartService->addItem($userId, $data['product_id'], $data['quantity'], $data['options'] ?? []);
        $stockErrors = $this->cartService->checkStock($userId);

        return $this->successResponse([
            'item'     => $item,
            'warnings' => $stockErrors ?: null,
        ], 'Đã thêm vào giỏ hàng');
    }

    /**
     * Cập nhật số lượng sản phẩm
     */
    public function updateItem(Request $request, string $itemKey): JsonResponse
    {
        $data = $request->validate([
            'quantity' => 'required|integer|min:0'
        ]);

        $userId = Auth::id();

        $result      = $this->cartService->updateItem($userId, $itemKey, $data['quantity']);
        $stockErrors = $this->cartService->checkStock($userId);
        $message     = $data['quantity'] > 0 ? 'Đã cập nhật' : 'Đã xóa';

        return $this->successResponse([
            'item'     => $result,
            'warnings' => $stockErrors ?: null,
        ], $message);
    }

    /**
     * Xóa sản phẩm khỏi giỏ hàng
     */
    public function removeItem(string $itemKey): JsonResponse
    {
        $userId = Auth::id();
        $cart = $this->cartService->removeItem($userId, $itemKey);

        return $this->successResponse($cart, 'Đã xóa sản phẩm');
    }

    /**
     * Xóa toàn bộ giỏ hàng
     */
    public function clear(): JsonResponse
    {
        $userId = Auth::id();
        $this->cartService->clear($userId);

        return $this->successResponse(null, 'Đã xóa giỏ hàng');
    }
}
