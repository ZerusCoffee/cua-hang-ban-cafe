<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('order_profit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->foreignId('order_item_id')->constrained('order_items')->onDelete('cascade');
            $table->foreignId('product_id')->nullable()->constrained('products')->onDelete('set null');
            $table->string('product_name'); // snapshot tên lúc đặt
            $table->string('product_sku');  // snapshot sku lúc đặt
            $table->integer('quantity');
            $table->decimal('unit_price', 10, 2);       // Giá bán lúc đặt
            $table->decimal('unit_cost', 10, 2);         // Giá cost nguyên liệu lúc đó
            $table->decimal('unit_profit', 10, 2);       // Lợi nhuận / sản phẩm
            $table->decimal('total_price', 10, 2);       // Tổng giá bán
            $table->decimal('total_cost', 10, 2);        // Tổng cost
            $table->decimal('total_profit', 10, 2);      // Tổng lợi nhuận
            $table->decimal('profit_margin', 5, 2);      // % lợi nhuận
            $table->json('options_snapshot')->nullable(); // Options đã chọn lúc đặt
            $table->json('cost_breakdown')->nullable();   // Chi tiết từng nguyên liệu
            $table->timestamp('logged_at');               // Thời điểm xác nhận đơn
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_profit_logs');
    }
};
