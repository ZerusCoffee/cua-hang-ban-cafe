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
        Schema::create("coupons", function (Blueprint $table) {
            $table->id();
            $table->string("code")->unique(); // Mã coupon
            $table->string("name")->nullable(); // "CHÀO MỪNG KHÁCH HÀNG MỚI"
            $table->text("description")->nullable(); // Mô tả

            //Thông tin giảm giá
            $table->enum("type", ["percentage", "fixed", "free_shipping"]); // Loại giảm giá
            $table->decimal("value", 10, 2); // Giá trị giảm giá
            $table->decimal("minimum_order_amount", 10, 2)->default(0); // Đơn hàng tối thiểu để áp dụng
            $table->decimal("maximum_discount_amount", 10, 2)->nullable(); // Giảm giá tối đa

            //Giới hạn sử dụng
            $table->integer("usage_limit")->nullable(); // Giới hạn số lần sử dụng
            $table->integer("usage_limit_per_customer")->nullable(); // Giới hạn số lần sử dụng cho mỗi khách hàng

            //Thời gian hiệu lực
            $table->dateTime("starts_at")->nullable(); // Thời gian bắt đầu
            $table->dateTime("expires_at")->nullable(); // Thời gian kết thúc

            $table->boolean("is_active")->default(true); // Trạng thái kích hoạt
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("coupons");
    }
};
