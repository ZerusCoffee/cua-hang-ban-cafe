<?php

namespace App\Http\Controllers;

use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __construct(private CartService $cartService) {}

    public function index(Request $request): JsonResponse
    {
        $token   = $request->attributes->get('cart_token');
        $summary = $this->cartService->summary($token);

        return $this->respond($summary, 'Lấy giỏ hàng thành công', $token);
    }

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

        $token       = $request->attributes->get('cart_token');
        $item        = $this->cartService->addItem($token, $data['product_id'], $data['quantity'], $data['options'] ?? []);
        $stockErrors = $this->cartService->checkStock($token);

        return $this->respond([
            'item'     => $item,
            'warnings' => $stockErrors ?: null,
        ], 'Đã thêm vào giỏ hàng', $token);
    }

    public function updateItem(Request $request, string $itemKey): JsonResponse
    {
        $data  = $request->validate(['quantity' => 'required|integer|min:0']);
        $token = $request->attributes->get('cart_token');

        $result      = $this->cartService->updateItem($token, $itemKey, $data['quantity']);
        $stockErrors = $this->cartService->checkStock($token);
        $message     = $data['quantity'] > 0 ? 'Đã cập nhật' : 'Đã xóa';

        return $this->respond([
            'item'     => $result,
            'warnings' => $stockErrors ?: null,
        ], $message, $token);
    }

    public function removeItem(Request $request, string $itemKey): JsonResponse
    {
        $token = $request->attributes->get('cart_token');
        $cart  = $this->cartService->removeItem($token, $itemKey);

        return $this->respond($cart, 'Đã xóa sản phẩm', $token);
    }

    public function clear(Request $request): JsonResponse
    {
        $token = $request->attributes->get('cart_token');
        $this->cartService->clear($token);

        return $this->respond(null, 'Đã xóa giỏ hàng', $token)
            ->cookie('cart_token', '', -1, '/');
    }

    private function respond(mixed $data, string $message, string $token): JsonResponse
    {
        return $this->successResponse($data, $message)
            ->header('X-Cart-Token', $token);
    }
}
