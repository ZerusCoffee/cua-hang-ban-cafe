<?php

namespace App\Services;

use App\Models\Ingredient;
use App\Models\Product;
use App\Models\ProductOptionModifier;
use Illuminate\Support\Facades\Redis;

class CartService
{
    private string $prefix = 'cart:';
    private int $ttl = 60 * 60 * 24 * 7; // 7 ngày

    private function key(string $cartToken): string
    {
        return $this->prefix . $cartToken;
    }

    /**
     * Lấy toàn bộ giỏ hàng
     */
    public function get(string $cartToken): array
    {
        $data = Redis::get($this->key($cartToken));
        return $data ? json_decode($data, true) : [];
    }

    /**
     * Thêm hoặc cập nhật item trong giỏ
     * $options format: [{ "product_option_id": 5, "option_id": 1, "group_name": "Size", "option_value": "L", "additional_price": 4000 }]
     */
    public function addItem(string $cartToken, int $productId, int $quantity, array $options = []): array
    {
        $product = Product::with('productOptions.option.group')->findOrFail($productId);

        // Tính giá = recommended_price + additional_price của các options
        $basePrice = floatval($product->recommended_price);
        $additionalPrice = collect($options)->sum('additional_price');
        $unitPrice = $basePrice + $additionalPrice;

        $cart = $this->get($cartToken);

        // Tạo key unique cho item dựa trên product + options đã chọn
        $itemKey = $this->makeItemKey($productId, $options);

        if (isset($cart[$itemKey])) {
            $cart[$itemKey]['quantity'] += $quantity;
        } else {
            $cart[$itemKey] = [
                'product_id'   => $productId,
                'product_name' => $product->name,
                'product_sku'  => $product->sku,
                'unit_price'   => $unitPrice,
                'quantity'     => $quantity,
                'options'      => $options,
                'image'        => $product->primaryImage?->image_path,
            ];
        }

        $this->save($cartToken, $cart);
        return $cart[$itemKey];
    }

    /**
     * Cập nhật số lượng item
     */
    public function updateItem(string $cartToken, string $itemKey, int $quantity): array
    {
        $cart = $this->get($cartToken);

        if (!isset($cart[$itemKey])) {
            abort(404, 'Item không tồn tại trong giỏ hàng');
        }

        if ($quantity <= 0) {
            return $this->removeItem($cartToken, $itemKey);
        }

        $cart[$itemKey]['quantity'] = $quantity;
        $this->save($cartToken, $cart);
        return $cart[$itemKey];
    }

    /**
     * Xóa 1 item khỏi giỏ
     */
    public function removeItem(string $cartToken, string $itemKey): array
    {
        $cart = $this->get($cartToken);
        unset($cart[$itemKey]);
        $this->save($cartToken, $cart);
        return $cart;
    }

    /**
     * Xóa toàn bộ giỏ
     */
    public function clear(string $cartToken): void
    {
        Redis::del($this->key($cartToken));
    }

    /**
     * Tính tổng giỏ hàng
     */
    public function summary(string $cartToken): array
    {
        $cart = $this->get($cartToken);
        $items = array_values($cart);

        $subtotal = collect($items)->sum(fn($i) => $i['unit_price'] * $i['quantity']);

        return [
            'items'    => $items,
            'count'    => count($items),
            'subtotal' => $subtotal,
        ];
    }

    /**
     * Kiểm tra tồn kho nguyên liệu cho toàn bộ giỏ
     * Trả về danh sách lỗi nếu không đủ
     */
    public function checkStock(string $cartToken): array
    {
        $cart = $this->get($cartToken);
        $errors = [];

        // Gom tổng nguyên liệu cần dùng cho toàn bộ giỏ
        $required = []; // [ingredient_id => total_amount]

        foreach ($cart as $item) {
            $product = Product::with('recipeDetails.ingredient')->find($item['product_id']);
            if (!$product) continue;

            // Base ingredients từ công thức
            $ingredientAmounts = [];
            foreach ($product->recipeDetails as $detail) {
                $ingredientAmounts[$detail->ingredient_id] =
                    ($ingredientAmounts[$detail->ingredient_id] ?? 0) + floatval($detail->amount);
            }

            // Áp dụng delta từ options
            $productOptionIds = collect($item['options'] ?? [])->pluck('product_option_id')->filter()->toArray();
            if (!empty($productOptionIds)) {
                $modifiers = ProductOptionModifier::whereIn('product_option_id', $productOptionIds)->get();
                foreach ($modifiers as $modifier) {
                    $id = $modifier->ingredient_id;
                    $ingredientAmounts[$id] = ($ingredientAmounts[$id] ?? 0) + floatval($modifier->delta_quantity);
                    $ingredientAmounts[$id] = max(0, $ingredientAmounts[$id]);
                }
            }

            // Nhân với quantity
            foreach ($ingredientAmounts as $ingredientId => $amountPerUnit) {
                $required[$ingredientId] = ($required[$ingredientId] ?? 0) + ($amountPerUnit * $item['quantity']);
            }
        }

        // So sánh với tồn kho thực tế
        if (!empty($required)) {
            $ingredients = Ingredient::whereIn('id', array_keys($required))->get()->keyBy('id');
            foreach ($required as $ingredientId => $totalNeeded) {
                $ingredient = $ingredients->get($ingredientId);
                if (!$ingredient) continue;
                if (floatval($ingredient->stock_quantity) < $totalNeeded) {
                    $errors[] = [
                        'ingredient' => $ingredient->name,
                        'needed'     => $totalNeeded,
                        'available'  => floatval($ingredient->stock_quantity),
                    ];
                }
            }
        }

        return $errors;
    }

    /**
     * Tạo unique key cho item dựa trên product_id + options đã chọn
     */
    public function makeItemKey(int $productId, array $options): string
    {
        $optionIds = collect($options)->pluck('product_option_id')->sort()->values()->toArray();
        return 'item_' . $productId . '_' . md5(json_encode($optionIds));
    }

    private function save(string $cartToken, array $cart): void
    {
        Redis::setex($this->key($cartToken), $this->ttl, json_encode($cart));
    }
}
