<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

class OrderService
{
    /**
     * Tạo order từ cart + data checkout
     */
    public function createFromCart(array $cart, int $customerId, array $data): Order
    {
        return DB::transaction(function () use ($cart, $customerId, $data) {
            $items    = array_values($cart);
            $subtotal = collect($items)->sum(fn($i) => $i['unit_price'] * $i['quantity']);

            [$coupon, $discountAmount] = $this->resolveCoupon($data['coupon_code'] ?? null, $subtotal);

            $order = Order::create([
                'customer_id'              => $customerId,
                'coupon_id'                => $coupon?->id,
                'subtotal'                 => $subtotal,
                'discount_amount'          => $discountAmount,
                'shipping_fee'             => 0,
                'tax_amount'               => 0,
                'total'                    => $subtotal - $discountAmount,
                'shipping_full_name'       => $data['shipping_full_name'],
                'shipping_phone'           => $data['shipping_phone'],
                'shipping_address_details' => $data['shipping_address_details'],
                'shipping_ward'            => $data['shipping_ward'],
                'shipping_province'        => $data['shipping_province'],
                'payment_method'           => $data['payment_method'],
                'payment_status'           => 'pending',
                'status'                   => 'pending',
                'customer_notes'           => $data['customer_notes'] ?? null,
            ]);

            foreach ($items as $item) {
                $order->items()->create([
                    'product_id'   => $item['product_id'],
                    'product_name' => $item['product_name'],
                    'product_sku'  => $item['product_sku'],
                    'price'        => $item['unit_price'],
                    'quantity'     => $item['quantity'],
                    'options'      => $item['options'] ?? [],
                    'subtotal'     => $item['unit_price'] * $item['quantity'],
                ]);
            }

            return $order;
        });
    }

    public function markPaid(Order $order, string $transactionId, array $paymentData = []): Order
    {
        $order->update([
            'payment_status'     => 'paid',
            'payment_ref'        => $transactionId,
            'payment_data'       => $paymentData,
        ]);

        return $order;
    }

    public function cancel(string $orderNumber): void
    {
        $order = Order::where('order_number', $orderNumber)->firstOrFail();
        $order->updateStatus('cancelled', 'Khách hàng huỷ đơn');
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function resolveCoupon(?string $code, float $subtotal): array
    {
        if (!$code) return [null, 0];

        $coupon = Coupon::where('code', $code)
            ->where('is_active', true)
            ->where(fn($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->firstOrFail();

        $discount = $coupon->type === 'percent'
            ? round($subtotal * $coupon->value / 100, 2)
            : floatval($coupon->value);

        return [$coupon, min($discount, $subtotal)];
    }
}
