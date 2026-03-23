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
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');

            $table->tinyInteger('rating'); // Đánh giá sao (1-5)
            $table->string('title')->nullable(); // Tiêu đề đánh giá
            $table->text('comment')->nullable(); // Bình luận
            $table->json('images')->nullable(); // Ảnh kèm theo

            $table->boolean('is_verified_purchase')->default(false); // Mua hàng đã xác minh
            $table->boolean('is_approved')->default(false); // Duyệt đánh giá

            // 1 khách chỉ review 1 lần / 1 sản phẩm (dù mua nhiều đơn)
            $table->unique(['product_id', 'customer_id']);

            // Query nhanh khi hiển thị review đã duyệt theo sản phẩm
            $table->index(['product_id', 'is_approved']);

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
