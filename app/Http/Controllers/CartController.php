<?php

namespace App\Http\Controllers;

use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CartController extends Controller
{
    public function __construct(private CartService $cartService) {}

    private function resolveToken(Request $request): string
    {
        return $request->header('X-Cart-Token') ?? Str::uuid()->toString();
    }

    /**
     * GET /api/v1/cart
     */
    public function index(Request $request): JsonResponse
    {
        $token = $this->resolveToken($request);
        $summary = $this->cartService->summary($token);

        return response()->json([
            'success' => true,
            'message' => 'Lấy giỏ hàng thành công',
            'data'    => $summary,
        ])->header('X-Cart-Token', $token);
    }

    /**
     * POST /api/v1/cart/items
     * Body: { product_id, quantity, options[] }
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

        $token = $this->resolveToken($request);
        $item  = $this->cartService->addItem(
            $token,
            $data['product_id'],
            $data['quantity'],
            $data['options'] ?? []
        );

        return response()->json([
            'success' => true,
            'message' => 'Đã thêm vào giỏ hàng',
            'data'    => $item,
        ])->header('X-Cart-Token', $token);
    }

    /**
     * PATCH /api/v1/cart/items/{itemKey}
     * Body: { quantity }
     */
    public function updateItem(Request $request, string $itemKey): JsonResponse
    {
        $data = $request->validate([
            'quantity' => 'required|integer|min:0',
        ]);

        $token  = $this->resolveToken($request);
        $result = $this->cartService->updateItem($token, $itemKey, $data['quantity']);

        return response()->json([
            'success' => true,
            'message' => 'Đã cập nhật giỏ hàng',
            'data'    => $result,
        ])->header('X-Cart-Token', $token);
    }

    /**
     * DELETE /api/v1/cart/items/{itemKey}
     */
    public function removeItem(Request $request, string $itemKey): JsonResponse
    {
        $token = $this->resolveToken($request);
        $cart  = $this->cartService->removeItem($token, $itemKey);

        return response()->json([
            'success' => true,
            'message' => 'Đã xóa sản phẩm khỏi giỏ hàng',
            'data'    => $cart,
        ])->header('X-Cart-Token', $token);
    }

    /**
     * DELETE /api/v1/cart
     */
    public function clear(Request $request): JsonResponse
    {
        $token = $this->resolveToken($request);
        $this->cartService->clear($token);

        return response()->json([
            'success' => true,
            'message' => 'Đã xóa giỏ hàng',
            'data'    => null,
        ])->header('X-Cart-Token', $token);
    }
}
