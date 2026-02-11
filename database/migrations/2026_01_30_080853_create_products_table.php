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
            $table->decimal('price', 10, 2); // base price <=> Size M
            $table->text('short_description')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false); // Sản phẩm nổi bật
            $table->boolean('has_variants')->default(false); // Có biến thể
            $table->string('meta_title')->nullable(); //Tìm kiếm theo tiêu đề
            $table->text('meta_description')->nullable(); //Tìm kiếm theo mô tả
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
