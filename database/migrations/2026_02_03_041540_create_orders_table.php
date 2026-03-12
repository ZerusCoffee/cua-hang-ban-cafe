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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique(); // Mã đơn hàng
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->foreignId('coupon_id')->nullable()->constrained('coupons')->onDelete('set null');

            //Order Amounts
            $table->decimal('subtotal', 10, 2); // Tổng tiền hàng
            $table->decimal('discount_amount', 10, 2)->default(0); // Tiền giảm giá
            $table->decimal('shipping_fee', 10, 2)->default(0); // Phí vận chuyển
            $table->decimal('tax_amount', 10, 2)->default(0); // Tiền thuế
            $table->decimal('total', 10, 2); // Tổng thanh toán

            //Shipping Information implode
            $table->string('shipping_full_name');
            $table->string('shipping_phone');
            $table->string('shipping_address_details'); // số nhà
            $table->string('shipping_ward'); //phường
            $table->string('shipping_province'); // thành phố

            //Payment & Status
            $table->enum('payment_method', ['vnpay', 'paypal', 'momo' , 'cod'])->default('cod');
            $table->enum('payment_status', ['pending', 'paid', 'failed', 'refunded'])->default('pending');
            $table->enum('status', ['pending', 'confirmed', 'delivered', 'cancelled'])->default('pending');
            $table->string('transaction_id')->nullable(); // Mã giao dịch
            $table->string('tracking_number')->nullable(); // Mã vận đơn str::random(8)
            $table->text('customer_notes')->nullable(); // Ghi chú của Customer
            $table->text('admin_notes')->nullable(); // Ghi chú của Admin
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
