<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderService
{
    /**
     * Tạo order từ cart + data checkout
     */
    public function createFromCart(array $cart, int $customerId, array $data): Order
    {
        return DB::transaction(function () use ($cart, $customerId, $data) {
            $items = array_values($cart);
            $subtotal = collect($items)->sum(fn($i) => $i['unit_price'] * $i['quantity']);

            [$coupon, $discountAmount] = $this->resolveCoupon(
                $data['coupon_code'] ?? null,
                $customerId,
                $subtotal
            );

             $order = Order::create([
                'customer_id'            => $customerId,
                'coupon_id'              => $coupon?->id,
                'subtotal'               => $subtotal,
                'discount_amount'        => $discountAmount,
                'shipping_fee'           => 0,
                'tax_amount'             => 0,
                'total'                  => $subtotal - $discountAmount,
                'shipping_full_name'     => $data['shipping_full_name'],
                'shipping_phone'         => $data['shipping_phone'],
                'shipping_address_details'=> $data['shipping_address_details'],
                'shipping_ward'          => $data['shipping_ward'],
                'shipping_province'      => $data['shipping_province'],
                'payment_method'         => $data['payment_method'],
                'payment_status'         => 'pending',
                'status'                 => 'pending',
                'customer_notes'         => $data['customer_notes'] ?? null,
            ]);

            foreach ($items as $item) {
                $order->items()->create([
                    'product_id' => $item['product_id'],
                    'product_name' => $item['product_name'],
                    'product_sku' => $item['product_sku'],
                    'product_image' => $item['image'] ?? null,
                    'price' => $item['unit_price'],
                    'quantity' => $item['quantity'],
                    'options' => $item['options'] ?? [],
                    'subtotal' => $item['unit_price'] * $item['quantity'],
                ]);
            }

            return $order;
        });
    }

    public function markPaid(Order $order, string $transactionId, array $paymentData = []): Order
    {
        $order->update([
            'payment_status' => 'paid',
            'payment_ref' => $transactionId,
        ]);

        return $order;
    }

    public function cancel(string $orderNumber): void
    {
        $order = Order::where('order_number', $orderNumber)->firstOrFail();
        $order->updateStatus('cancelled', 'Khách hàng huỷ đơn');
    }

    private function resolveCoupon(?string $code, int $customerId, float $subtotal): array
    {
        if (!$code) {
            return [null, 0];
        }

        $result = app(CouponService::class)->validate($code, $customerId, $subtotal);

        if (!$result['valid']) {
            throw new \Exception($result['message']);
        }

        return [$result['coupon'], $result['discount_amount']];
    }
}
