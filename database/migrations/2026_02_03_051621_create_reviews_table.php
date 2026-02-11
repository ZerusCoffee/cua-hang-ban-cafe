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
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->tinyInteger('rating'); // Đánh giá sao (1-5)
            $table->string('title')->nullable(); // Tiêu đề đánh giá
            $table->text('comment')->nullable(); // Bình luận
            $table->boolean('is_verified_purchase')->default(false); // Mua hàng đã xác minh
            $table->boolean('is_approved')->default(false); // Duyệt đánh giá

            // Đảm bảo mỗi khách hàng chỉ đánh giá một lần cho mỗi sản phẩm
            $table->unique(['product_id', 'customer_id']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
