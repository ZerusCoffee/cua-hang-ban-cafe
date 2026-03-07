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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('categories')->onDelete('cascade');
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('sku')->unique();
            $table->decimal('recommended_price', 15, 2)->default(0); // Giá bán đề xuất
            $table->decimal('profit_rate', 20, 2)->default(0); //Tỉ suất lợi nhuận
            $table->text('short_description')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_featured')->default(false); // Sản phẩm nổi bật
            $table->boolean('is_active')->default(true); // Ẩn không bán/ Hiện thị đang bán
            $table->integer('view_count')->default(0); // Số lượt xem
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
