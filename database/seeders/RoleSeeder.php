<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Helper: tạo full 12 permissions cho một resource.
     */
    private function allOf(string $resource): array
    {
        return [
            "ViewAny:{$resource}",
            "View:{$resource}",
            "Create:{$resource}",
            "Update:{$resource}",
            "Delete:{$resource}",
            "DeleteAny:{$resource}",
            "Restore:{$resource}",
            "RestoreAny:{$resource}",
            "ForceDelete:{$resource}",
            "ForceDeleteAny:{$resource}",
            "Replicate:{$resource}",
            "Reorder:{$resource}",
        ];
    }

    /**
     * Helper: chỉ quyền đọc cho một resource.
     */
    private function readOnly(string $resource): array
    {
        return [
            "ViewAny:{$resource}",
            "View:{$resource}",
        ];
    }

    /**
     * Helper: quyền đọc + tạo + sửa (không xoá, không force).
     */
    private function readWrite(string $resource): array
    {
        return [
            "ViewAny:{$resource}",
            "View:{$resource}",
            "Create:{$resource}",
            "Update:{$resource}",
        ];
    }

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // ────────────────────────────────────────────────────────
        // SUPER ADMIN — toàn quyền tất cả resources
        // ────────────────────────────────────────────────────────
        $superAdmin = Role::firstOrCreate(
            ['name' => 'super_admin', 'guard_name' => 'web']
        );
        $superAdmin->syncPermissions(Permission::all());

        // ────────────────────────────────────────────────────────
        // MANAGER — quản lý toàn bộ nghiệp vụ
        //   • Full quyền: Product, Category, OptionGroup, Coupon,
        //                 Order, Customer, Review, Supplier
        //   • ReadOnly  : User, Role
        //   • Không có  : ImportOrder, Ingredient, Unit (kho)
        // ────────────────────────────────────────────────────────
        $manager = Role::firstOrCreate(
            ['name' => 'manager', 'guard_name' => 'web']
        );
        $manager->syncPermissions(array_merge(
            $this->readOnly('User'),
            $this->readOnly('Role'),
            $this->allOf('Customer'),
            $this->allOf('Review'),
            $this->allOf('Product'),
            $this->allOf('Category'),
            $this->allOf('OptionGroup'),
            $this->allOf('Coupon'),
            $this->allOf('Order'),
            $this->allOf('Supplier'),
        ));

        // ────────────────────────────────────────────────────────
        // SALES — tập trung bán hàng & chăm sóc khách
        //   • Full quyền: Order, Customer
        //   • ReadWrite  : Coupon (tạo mã, sửa — không xoá)
        //   • ReadOnly   : Product, Category, Review
        //   • Không có   : User, Role, Supplier, ImportOrder, kho
        // ────────────────────────────────────────────────────────
        $sales = Role::firstOrCreate(
            ['name' => 'sales', 'guard_name' => 'web']
        );
        $sales->syncPermissions(array_merge(
            $this->allOf('Order'),
            $this->allOf('Customer'),
            $this->readWrite('Coupon'),
            $this->readOnly('Product'),
            $this->readOnly('Category'),
            $this->readOnly('Review'),
        ));

        // ────────────────────────────────────────────────────────
        // WAREHOUSE — quản lý kho, nhập hàng, nguyên liệu
        //   • Full quyền: ImportOrder, Ingredient, Unit, Supplier
        //   • ReadOnly   : Product, Order (để chuẩn bị hàng)
        //   • Không có   : User, Role, Customer, Coupon, Review...
        // ────────────────────────────────────────────────────────
        $warehouse = Role::firstOrCreate(
            ['name' => 'warehouse', 'guard_name' => 'web']
        );
        $warehouse->syncPermissions(array_merge(
                $this->allOf('ImportOrder'),
                $this->allOf('Ingredient'),
                $this->allOf('Unit'),
                $this->allOf('Supplier'),
                $this->readOnly('Product'),
                $this->readOnly('Order'),
            )
        );
    }
}
