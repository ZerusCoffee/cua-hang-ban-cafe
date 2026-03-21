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
        // 1. Tạo 1 Supplier
        $supplier = Supplier::create([
            'name' => 'Nhà cung cấp Nguyên Liệu Sạch',
            'phone' => '0987654321',
            'email' => 'contact@nguyenlieusach.com',
            'address' => '123 Đường ABC, Quận XYZ, TP.HCM',
            'notes' => 'Nhà cung cấp sỉ các loại nguyên liệu pha chế'
        ]);

        // 2. Tạo ImportOrder
        $importOrder = ImportOrder::create([
            'supplier_id' => $supplier->id,
            'status' => 'draft',
            'notes' => 'Nhập hàng đợt 1',
        ]);

        // 3. Lấy tất cả Ingredient và tạo ImportOrderDetail
        $ingredients = Ingredient::all();

        foreach ($ingredients as $ingredient) {
            // Xác định số lượng nhập và giá tuỳ theo loại nguyên liệu để giá được làm tròn hợp lý
            $quantity = 0;
            $unitPrice = 0;

            // Dựa vào đơn vị để quyết định số lượng và giá
            if ($ingredient->unit->name === 'Gram' || $ingredient->unit->name === 'Milliliter') {
                $quantity = rand(5, 20) * 1000; // 5kg - 20kg hoặc 5L - 20L (làm tròn số theo 1000 cho đẹp)
                $unitPrice = rand(5, 30) * 10; // Giá cho mỗi gram/ml: 50đ - 300đ (số chẵn)
            } else if ($ingredient->unit->name === 'Piece') {
                $quantity = rand(10, 50) * 10; // 100 - 500 cái (số chẵn 10)
                $unitPrice = rand(3, 15) * 1000; // Giá mỗi cái: 3000đ - 15000đ (chẵn 1000)
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

        // 4. Complete ImportOrder (để cập nhật tồn kho, giá vốn và ghi log)
        // Gọi `complete` thông qua một instance mới được query lại để đảm bảo đã load các details
        $orderToComplete = ImportOrder::find($importOrder->id);

        // Không gọi $orderToComplete->complete() qua Model vì Model Events `complete()`
        // Đôi lúc có thể làm tròn sai hoặc ko update lại DB chuẩn,
        // do làm tròn ((int) round($averageCost / 1000) * 1000) bên ImportOrder Model
        // sẽ vô tình biến giá vốn nhỏ của Gram/Ml thành 0

        $orderToComplete->complete();
    }
}
