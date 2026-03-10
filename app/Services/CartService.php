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

    // ─── Redis helpers ────────────────────────────────────────────────────────

    private function key(string $token): string
    {
        return $this->prefix . $token;
    }

    private function load(string $token): array
    {
        $data = Redis::get($this->key($token));

        if ($data) {
            Redis::expire($this->key($token), $this->ttl);
            return json_decode($data, true);
        }

        return [];
    }

    private function save(string $token, array $cart): void
    {
        Redis::setex($this->key($token), $this->ttl, json_encode($cart));
    }

    // ─── Token ────────────────────────────────────────────────────────────────

    public function exists(string $token): bool
    {
        return Redis::exists($this->key($token)) > 0;
    }

    public function init(string $token): void
    {
        $this->save($token, []);
    }

    public function get(string $token): array
    {
        return $this->load($token);
    }

    public function summary(string $token): array
    {
        $items = array_values($this->load($token));

        return [
            'items'    => $items,
            'count'    => count($items),
            'subtotal' => collect($items)->sum(fn($i) => $i['unit_price'] * $i['quantity']),
        ];
    }

    public function addItem(string $token, int $productId, int $quantity, array $options = []): array
    {
        $product = Product::findOrFail($productId);
        $unitPrice = floatval($product->recommended_price)
                   + collect($options)->sum('additional_price');

        $cart    = $this->load($token);
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

        $this->save($token, $cart);

        return $cart[$itemKey];
    }

    public function updateItem(string $token, string $itemKey, int $quantity): array
    {
        $cart = $this->load($token);

        abort_unless(isset($cart[$itemKey]), 404, 'Item không tồn tại trong giỏ hàng');

        if ($quantity <= 0) {
            return $this->removeItem($token, $itemKey);
        }

        $cart[$itemKey]['quantity'] = $quantity;
        $this->save($token, $cart);

        return $cart[$itemKey];
    }

    public function removeItem(string $token, string $itemKey): array
    {
        $cart = $this->load($token);
        unset($cart[$itemKey]);
        $this->save($token, $cart);

        return $cart;
    }

    public function clear(string $token): void
    {
        Redis::del($this->key($token));
    }

    public function checkStock(string $token): array
    {
        $required = $this->aggregateRequired($token);

        if (empty($required)) return [];

        $ingredients = Ingredient::whereIn('id', array_keys($required))->get()->keyBy('id');

        return collect($required)
            ->filter(fn($needed, $id) =>
                isset($ingredients[$id]) && floatval($ingredients[$id]->stock) < $needed
            )
            ->map(fn($needed, $id) => [
                'ingredient' => $ingredients[$id]->name,
                'needed'     => $needed,
                'available'  => floatval($ingredients[$id]->stock),
            ])
            ->values()
            ->toArray();
    }

    public function makeItemKey(int $productId, array $options): string
    {
        $ids = collect($options)->pluck('product_option_id')->sort()->values()->toArray();

        return 'item_' . $productId . '_' . md5(json_encode($ids));
    }

    private function aggregateRequired(string $token): array
    {
        $cart     = $this->load($token);
        $required = [];

        foreach ($cart as $item) {
            $product = Product::with('recipeDetails')->find($item['product_id']);
            if (!$product) continue;

            $amounts = collect($product->recipeDetails)
                ->mapWithKeys(fn($d) => [$d->ingredient_id => floatval($d->amount)])
                ->toArray();

            // Áp dụng delta từ options
            $optionIds = collect($item['options'] ?? [])->pluck('product_option_id')->filter()->toArray();
            if (!empty($optionIds)) {
                ProductOptionModifier::whereIn('product_option_id', $optionIds)
                    ->get()
                    ->each(function ($mod) use (&$amounts) {
                        $amounts[$mod->ingredient_id] = max(
                            0,
                            ($amounts[$mod->ingredient_id] ?? 0) + floatval($mod->delta_quantity)
                        );
                    });
            }

            foreach ($amounts as $ingredientId => $amountPerUnit) {
                $required[$ingredientId] = ($required[$ingredientId] ?? 0) + ($amountPerUnit * $item['quantity']);
            }
        }

        return $required;
    }
}
