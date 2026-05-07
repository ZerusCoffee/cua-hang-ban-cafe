<?php

namespace App\Services\Payment;

use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaypalPaymentService implements PaymentServiceInterface
{
    private string $baseUrl;
    private string $clientId;
    private string $secret;

    public function __construct(private OrderService $orderService)
    {
        $this->baseUrl = config('payment.paypal.environment') === 'production'
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';
        $this->clientId = config('payment.paypal.client_id');
        $this->secret = config('payment.paypal.secret');
    }

    public function handle(Order $order, array $data): JsonResponse
    {
        $accessToken = $this->getAccessToken();

        $response = Http::withToken($accessToken)->post("{$this->baseUrl}/v2/checkout/orders", [
            'intent' => 'CAPTURE',
            'purchase_units' => [[
                'reference_id' => $order->order_number,
                'amount' => [
                    'currency_code' => 'USD',
                    'value' => number_format($order->total / 25000, 2, '.', '')],// giả sử tỉ giá 25000
            ]],
        ]);

        $result = $response->json();

        if (!isset($result['id'])) {
            return response()->json(['status' => 'error', 'message' => 'Không thể tạo thanh toán PayPal'], 400);
        }

        $order->update(['paypal_order_id' => $result['id']]);

        return response()->json([
            'status' => 'success',
            'data' => [
                'order_id' => $result['id'],
                'total' => $order->total,
            ],
        ]);
    }

    public function captureOrder(Request $request): JsonResponse
    {
        $request->validate(['order_id' => 'required|string']);
        $accessToken = $this->getAccessToken();

        $orderResponse = Http::withToken($accessToken)
            ->get("{$this->baseUrl}/v2/checkout/orders/{$request->order_id}");

        if ($orderResponse->failed()) {
            return response()->json(['status' => 'error', 'message' => 'Không thể lấy thông tin đơn hàng PayPal'], 400);
        }

        $orderData = $orderResponse->json();
        $referenceId = $orderData['purchase_units'][0]['reference_id'] ?? null;

        if (!$referenceId) {
            return response()->json(['status' => 'error', 'message' => 'Không tìm thấy mã đơn hàng trong giao dịch PayPal'], 400);
        }

        $order = Order::where('order_number', $referenceId)->firstOrFail();

        $captureResponse = Http::withToken($accessToken)
            ->withBody('{}', 'application/json')
            ->post("{$this->baseUrl}/v2/checkout/orders/{$request->order_id}/capture");

        $result = $captureResponse->json();

        if (($result['status'] ?? '') !== 'COMPLETED') {
            Log::channel('daily')->error('PayPal capture failed', [
                'order_number' => $referenceId,
                'paypal_order_id' => $request->order_id,
                'http_status_code' => $captureResponse->status(),
                'paypal_response' => $result,
            ]);
        }

        if (($result['status'] ?? '') === 'COMPLETED') {
            $this->orderService->markPaid($order, $request->order_id, $result);
            $order->updateStatus('confirmed', 'Thanh toán PayPal thành công');

            return response()->json([
                'status' => 'success',
                'details' => array_merge($result, ['order_number' => $order->order_number]),
            ]);
        }

        $order->update(['payment_status' => 'failed']);
        return response()->json(['status' => 'error', 'message' => 'Thanh toán thất bại'], 400);
    }

    public function callback(Request $request): JsonResponse
    {
        $token = $request->input('token');
        $orderNumber = $request->input('order_number');
        $accessToken = $this->getAccessToken();

        $response = Http::withToken($accessToken)
            ->post("{$this->baseUrl}/v2/checkout/orders/{$token}/capture");

        $result = $response->json();


        $order = Order::where('order_number', $orderNumber)->firstOrFail();

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
