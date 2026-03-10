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
        Schema::create('ingredient_import_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ingredient_id')->constrained('ingredients')->cascadeOnDelete();
            $table->foreignId('import_order_id')->constrained('import_orders')->cascadeOnDelete();
            $table->string('import_order_code');        // snapshot mã phiếu
            $table->decimal('quantity', 10, 3);         // số lượng nhập lần này
            $table->decimal('stock_before', 10, 3);     // tồn trước khi nhập
            $table->decimal('stock_after', 10, 3);      // tồn sau khi nhập
            $table->decimal('unit_price', 15, 2);       // giá nhập lần này
            $table->decimal('cost_price_before', 15, 2);// giá bình quân trước
            $table->decimal('cost_price_after', 15, 2); // giá bình quân sau
            $table->timestamp('imported_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ingredient_import_logs');
    }
};
