<?php

namespace App\Services\Payment;

use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

interface PaymentServiceInterface
{
    /**
     * Khởi tạo thanh toán — trả về URL redirect hoặc confirm luôn (COD)
     */
    public function handle(Order $order, array $data): JsonResponse;

    /**
     * Xử lý callback từ cổng thanh toán
     */
    public function callback(Request $request): JsonResponse;

}
