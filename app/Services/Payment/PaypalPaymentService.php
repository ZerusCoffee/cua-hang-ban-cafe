<?php

namespace App\Services\Payment;

use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class PaypalPaymentService implements PaymentServiceInterface
{
    private string $baseUrl;
    private string $clientId;
    private string $secret;

    public function __construct(private OrderService $orderService)
    {
        $this->baseUrl  = config('payment.paypal.environment') === 'production'
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';
        $this->clientId = config('payment.paypal.client_id');
        $this->secret   = config('payment.paypal.secret');
    }

    public function handle(Order $order, array $data): JsonResponse
    {
        $accessToken = $this->getAccessToken();

        $response = Http::withToken($accessToken)->post("{$this->baseUrl}/v2/checkout/orders", [
            'intent'         => 'CAPTURE',
            'purchase_units' => [[
                'reference_id' => $order->order_number,
                'amount'       => ['currency_code' => 'VND', 'value' => $order->total],
            ]],
            'payment_source' => ['paypal' => ['experience_context' => [
                'return_url' => route('paypal.callback'),
                'cancel_url' => route('checkout.cancel', $order->order_number),
                'user_action' => 'PAY_NOW',
            ]]],
        ]);

        $result     = $response->json();
        $approveUrl = collect($result['links'] ?? [])->firstWhere('rel', 'approve')['href'] ?? null;

        if (!$approveUrl) {
            return response()->json(['status' => 'error', 'message' => 'Không thể tạo thanh toán PayPal'], 400);
        }

        return response()->json([
            'status'  => 'success',
            'message' => 'Chuyển hướng đến PayPal',
            'data'    => [
                'order_number'   => $order->order_number,
                'payment_url'    => $approveUrl,
                'paypal_order_id'=> $result['id'],
                'total'          => $order->total,
            ],
        ]);
    }

    public function callback(Request $request): JsonResponse
    {
        $token       = $request->input('token');
        $orderNumber = $request->input('order_number');
        $accessToken = $this->getAccessToken();

        $response = Http::withToken($accessToken)
            ->post("{$this->baseUrl}/v2/checkout/orders/{$token}/capture");

        $result = $response->json();
        $order  = Order::where('order_number', $orderNumber)->firstOrFail();

        if ($result['status'] === 'COMPLETED') {
            $this->orderService->markPaid($order, $token, $result);
            $order->updateStatus('confirmed', 'Thanh toán PayPal thành công');

            return response()->json(['status' => 'success']);
        }

        $order->update(['payment_status' => 'failed']);

        return response()->json(['status' => 'error'], 400);
    }

    private function getAccessToken(): string
    {
        $response = Http::withBasicAuth($this->clientId, $this->secret)
            ->asForm()
            ->post("{$this->baseUrl}/v1/oauth2/token", ['grant_type' => 'client_credentials']);

        return $response->json()['access_token'];
    }
}
