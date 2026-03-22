<?php

namespace Database\Seeders;

use App\Models\ImportOrder;
use App\Models\ImportOrderDetail;
use App\Models\Ingredient;
use App\Models\Supplier;
use Illuminate\Database\Seeder;

class ImportOrderSeeder extends Seeder
{
    public function run(): void
    {
        $supplier = Supplier::create([
            'name' => 'Nhà cung cấp Nguyên Liệu Sạch',
            'phone' => '0987654321',
            'email' => 'contact@nguyenlieusach.com',
            'address' => '123 Đường ABC, Quận XYZ, TP.HCM',
            'notes' => 'Nhà cung cấp sỉ các loại nguyên liệu pha chế'
        ]);

        $importOrder = ImportOrder::create([
            'supplier_id' => $supplier->id,
            'status' => 'draft',
            'notes' => 'Nhập hàng đợt 1',
        ]);

        $ingredients = Ingredient::all();

        foreach ($ingredients as $ingredient) {
            $quantity = 0;
            $unitPrice = 0;

            // Dựa vào đơn vị để quyết định số lượng và giá
            if ($ingredient->unit->name === 'Gram' || $ingredient->unit->name === 'Milliliter') {
                $quantity = rand(5, 20) * 1000;
                $unitPrice = rand(5, 30) * 10;
            } else if ($ingredient->unit->name === 'Piece') {
                $quantity = rand(10, 50) * 10;
                $unitPrice = rand(3, 15) * 1000;
            } else {
                $quantity = rand(1, 10) * 10;
                $unitPrice = rand(10, 50) * 1000;
            }

            ImportOrderDetail::create([
                'import_order_id' => $importOrder->id,
                'ingredient_id' => $ingredient->id,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
            ]);
        }

        $orderToComplete = ImportOrder::find($importOrder->id);
        $orderToComplete->complete();
    }
}
