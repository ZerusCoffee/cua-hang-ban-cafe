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
        Schema::create('ingredients', function (Blueprint $table) {
            $table->id();
            $table->string("name");
            $table->foreignId('unit_id')->constrained('units');
            $table->string("sku")->nullable()->unique();
            $table->decimal("cost_price", 15, 2)->default(0);
            $table->decimal('stock', 10, 3)->default(0);
            $table->decimal('threshold', 10, 3)->default(50);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ingredients');
    }
};
