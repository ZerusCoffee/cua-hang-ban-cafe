<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use App\Models\Order;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function __construct(private CartService $cartService) {}

    /**
     * POST /api/v1/order/checkout
     * Tạo order từ giỏ hàng — cần auth:sanctum
     */
    public function checkout(Request $request): JsonResponse
    {
        $data = $request->validate([
            'cart_token'               => 'required|string',
            'shipping_full_name'       => 'required|string|max:255',
            'shipping_phone'           => 'required|string|max:20',
            'shipping_address_details' => 'required|string|max:255',
            'shipping_ward'            => 'required|string|max:100',
            'shipping_province'        => 'required|string|max:100',
            'payment_method'           => 'required|in:credit_card,paypal,cash_on_delivery',
            'customer_notes'           => 'nullable|string',
            'coupon_code'              => 'nullable|string',
        ]);

        $cartToken = $data['cart_token'];
        $cart = $this->cartService->get($cartToken);

        if (empty($cart)) {
            return $this->errorResponse('Giỏ hàng trống', 422);
        }

        // Kiểm tra tồn kho nguyên liệu
        $stockErrors = $this->cartService->checkStock($cartToken);
        if (!empty($stockErrors)) {
            return $this->errorResponse('Không đủ nguyên liệu', 422, [
                'stock_errors' => $stockErrors,
            ]);
        }

        // Xử lý coupon
        $coupon = null;
        $discountAmount = 0;
        if (!empty($data['coupon_code'])) {
            $coupon = Coupon::where('code', $data['coupon_code'])
                ->where('is_active', true)
                ->where(fn($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
                ->first();

            if (!$coupon) {
                return $this->errorResponse('Mã giảm giá không hợp lệ hoặc đã hết hạn', 422);
            }
        }

        try {
            $order = DB::transaction(function () use ($data, $cart, $coupon, $discountAmount, $request) {
                $items = array_values($cart);
                $subtotal = collect($items)->sum(fn($i) => $i['unit_price'] * $i['quantity']);

                // Tính discount
                if ($coupon) {
                    $discountAmount = $coupon->type === 'percent'
                        ? round($subtotal * $coupon->value / 100, 2)
                        : floatval($coupon->value);
                    $discountAmount = min($discountAmount, $subtotal);
                }

                $total = $subtotal - $discountAmount;

                $order = Order::create([
                    'customer_id'              => $request->user()->id, // lấy từ auth
                    'coupon_id'                => $coupon?->id,
                    'subtotal'                 => $subtotal,
                    'discount_amount'          => $discountAmount,
                    'shipping_fee'             => 0,
                    'tax_amount'               => 0,
                    'total'                    => $total,
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
                        'options'      => $item['options'],
                        'subtotal'     => $item['unit_price'] * $item['quantity'],
                    ]);
                }

                return $order;
            });

            // Xóa giỏ sau khi đặt thành công
            $this->cartService->clear($cartToken);

            return $this->successResponse([
                'order_number' => $order->order_number,
                'total'        => $order->total,
                'status'       => $order->status,
            ], 'Đặt hàng thành công', code: 201);

        } catch (\Exception $e) {
            return $this->errorResponse('Có lỗi xảy ra khi đặt hàng: ' . $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/v1/order
     * Danh sách đơn hàng của customer đang login
     */
    public function index(Request $request): JsonResponse
    {
        $orders = Order::where('customer_id', $request->user()->id)
            ->with(['items'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return $this->successResponse($orders, 'Lấy danh sách đơn hàng thành công');
    }

    /**
     * GET /api/v1/order/{orderNumber}
     * Chi tiết đơn hàng — chỉ xem được đơn của chính mình
     */
    public function show(Request $request, string $orderNumber): JsonResponse
    {
        $order = Order::where('order_number', $orderNumber)
            ->where('customer_id', $request->user()->id) // bảo mật: chỉ xem đơn của mình
            ->with(['items', 'statusHistories'])
            ->firstOrFail();

        return $this->successResponse($order, 'Lấy chi tiết đơn hàng thành công');
    }
}
