<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('coupon_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coupon_id')->constrained('coupons')->onDelete('cascade');
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');

            // Lưu lại số tiền thực tế đã giảm tại thời điểm dùng
            $table->decimal('discount_amount', 10, 2)->default(0);

            $table->timestamps();

            // Indexes để query nhanh
            $table->index(['coupon_id', 'customer_id']); // khách này dùng coupon này mấy lần?
            $table->index('order_id');                   //order này dùng coupon nào?
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coupon_usages');
    }
};
