<?php

namespace App\Services;

use App\Models\Ingredient;
use App\Models\Product;
use App\Models\ProductOptionModifier;
use Illuminate\Support\Facades\Redis;

class CartService
{
    private string $prefix = 'cart:user:';
    private int    $ttl    = 60 * 60 * 24 * 7; // 7 ngày

    // ─── Redis helpers ────────────────────────────────────────────────────────

    private function key(int $userId): string
    {
        return $this->prefix . $userId;
    }

    private function load(int $userId): array
    {
        $data = Redis::get($this->key($userId));

        if ($data) {
            Redis::expire($this->key($userId), $this->ttl);
            return json_decode($data, true);
        }

        return [];
    }

    private function save(int $userId, array $cart): void
    {
        Redis::setex($this->key($userId), $this->ttl, json_encode($cart));
    }

    // ─── Cart CRUD ────────────────────────────────────────────────────────────

    public function get(int $userId): array
    {
        return $this->load($userId);
    }

    public function summary(int $userId): array
    {
        $items = array_values($this->load($userId));

        return [
            'items'    => $items,
            'count'    => count($items),
            'subtotal' => collect($items)->sum(fn($i) => $i['unit_price'] * $i['quantity']),
        ];
    }

    public function addItem(int $userId, int $productId, int $quantity, array $options = []): array
    {
        $product   = Product::findOrFail($productId);
        $unitPrice = floatval($product->recommended_price)
                   + collect($options)->sum('additional_price');

        $cart    = $this->load($userId);
        $itemKey = $this->makeItemKey($productId, $options);

        if (isset($cart[$itemKey])) {
            $cart[$itemKey]['quantity'] += $quantity;
        } else {
            $cart[$itemKey] = [
                'item_key'     => $itemKey,
                'product_id'   => $productId,
                'product_name' => $product->name,
                'product_sku'  => $product->sku,
                'unit_price'   => $unitPrice,
                'quantity'     => $quantity,
                'options'      => $options,
                'image'        => $product->primaryImage?->image_path,
            ];
        }

        $this->save($userId, $cart);

        return $cart[$itemKey];
    }

    public function updateItem(int $userId, string $itemKey, int $quantity): array
    {
        $cart = $this->load($userId);

        abort_unless(isset($cart[$itemKey]), 404, 'Item không tồn tại trong giỏ hàng');

        if ($quantity <= 0) {
            return $this->removeItem($userId, $itemKey);
        }

        $cart[$itemKey]['quantity'] = $quantity;
        $this->save($userId, $cart);

        return $cart[$itemKey];
    }

    public function removeItem(int $userId, string $itemKey): array
    {
        $cart = $this->load($userId);
        unset($cart[$itemKey]);
        $this->save($userId, $cart);

        return $cart;
    }

    public function clear(int $userId): void
    {
        Redis::del($this->key($userId));
    }

    // ─── Stock check ──────────────────────────────────────────────────────────

    public function checkStock(int $userId): array
    {
        $required = $this->aggregateRequired($userId);

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

    // ─── Helpers ──────────────────────────────────────────────────────────────

    public function makeItemKey(int $productId, array $options): string
    {
        $ids = collect($options)->pluck('product_option_id')->sort()->values()->toArray();

        return 'item_' . $productId . '_' . md5(json_encode($ids));
    }

    private function aggregateRequired(int $userId): array
    {
        $cart     = $this->load($userId);
        $required = [];

        foreach ($cart as $item) {
            $product = Product::with('recipeDetails')->find($item['product_id']);
            if (!$product) continue;

            $amounts = collect($product->recipeDetails)
                ->mapWithKeys(fn($d) => [$d->ingredient_id => floatval($d->amount)])
                ->toArray();

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
