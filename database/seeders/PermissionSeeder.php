<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    private array $permissions = [
        // ── User ──────────────────────────────────────────────
        'ViewAny:User',
        'View:User',
        'Create:User',
        'Update:User',
        'Delete:User',
        'DeleteAny:User',
        'Restore:User',
        'RestoreAny:User',
        'ForceDelete:User',
        'ForceDeleteAny:User',
        'Replicate:User',
        'Reorder:User',

        // ── Role ──────────────────────────────────────────────
        'ViewAny:Role',
        'View:Role',
        'Create:Role',
        'Update:Role',
        'Delete:Role',
        'DeleteAny:Role',
        'Restore:Role',
        'RestoreAny:Role',
        'ForceDelete:Role',
        'ForceDeleteAny:Role',
        'Replicate:Role',
        'Reorder:Role',

        // ── Customer ──────────────────────────────────────────
        'ViewAny:Customer',
        'View:Customer',
        'Create:Customer',
        'Update:Customer',
        'Delete:Customer',
        'DeleteAny:Customer',
        'Restore:Customer',
        'RestoreAny:Customer',
        'ForceDelete:Customer',
        'ForceDeleteAny:Customer',
        'Replicate:Customer',
        'Reorder:Customer',

        // ── Review ────────────────────────────────────────────
        'ViewAny:Review',
        'View:Review',
        'Create:Review',
        'Update:Review',
        'Delete:Review',
        'DeleteAny:Review',
        'Restore:Review',
        'RestoreAny:Review',
        'ForceDelete:Review',
        'ForceDeleteAny:Review',
        'Replicate:Review',
        'Reorder:Review',

        // ── Product ───────────────────────────────────────────
        'ViewAny:Product',
        'View:Product',
        'Create:Product',
        'Update:Product',
        'Delete:Product',
        'DeleteAny:Product',
        'Restore:Product',
        'RestoreAny:Product',
        'ForceDelete:Product',
        'ForceDeleteAny:Product',
        'Replicate:Product',
        'Reorder:Product',

        // ── Category ──────────────────────────────────────────
        'ViewAny:Category',
        'View:Category',
        'Create:Category',
        'Update:Category',
        'Delete:Category',
        'DeleteAny:Category',
        'Restore:Category',
        'RestoreAny:Category',
        'ForceDelete:Category',
        'ForceDeleteAny:Category',
        'Replicate:Category',
        'Reorder:Category',

        // ── OptionGroup ───────────────────────────────────────
        'ViewAny:OptionGroup',
        'View:OptionGroup',
        'Create:OptionGroup',
        'Update:OptionGroup',
        'Delete:OptionGroup',
        'DeleteAny:OptionGroup',
        'Restore:OptionGroup',
        'RestoreAny:OptionGroup',
        'ForceDelete:OptionGroup',
        'ForceDeleteAny:OptionGroup',
        'Replicate:OptionGroup',
        'Reorder:OptionGroup',

        // ── Coupon ────────────────────────────────────────────
        'ViewAny:Coupon',
        'View:Coupon',
        'Create:Coupon',
        'Update:Coupon',
        'Delete:Coupon',
        'DeleteAny:Coupon',
        'Restore:Coupon',
        'RestoreAny:Coupon',
        'ForceDelete:Coupon',
        'ForceDeleteAny:Coupon',
        'Replicate:Coupon',
        'Reorder:Coupon',

        // ── Order ─────────────────────────────────────────────
        'ViewAny:Order',
        'View:Order',
        'Create:Order',
        'Update:Order',
        'Delete:Order',
        'DeleteAny:Order',
        'Restore:Order',
        'RestoreAny:Order',
        'ForceDelete:Order',
        'ForceDeleteAny:Order',
        'Replicate:Order',
        'Reorder:Order',

        // ── ImportOrder ───────────────────────────────────────
        'ViewAny:ImportOrder',
        'View:ImportOrder',
        'Create:ImportOrder',
        'Update:ImportOrder',
        'Delete:ImportOrder',
        'DeleteAny:ImportOrder',
        'Restore:ImportOrder',
        'RestoreAny:ImportOrder',
        'ForceDelete:ImportOrder',
        'ForceDeleteAny:ImportOrder',
        'Replicate:ImportOrder',
        'Reorder:ImportOrder',

        // ── Supplier ──────────────────────────────────────────
        'ViewAny:Supplier',
        'View:Supplier',
        'Create:Supplier',
        'Update:Supplier',
        'Delete:Supplier',
        'DeleteAny:Supplier',
        'Restore:Supplier',
        'RestoreAny:Supplier',
        'ForceDelete:Supplier',
        'ForceDeleteAny:Supplier',
        'Replicate:Supplier',
        'Reorder:Supplier',

        // ── Ingredient ────────────────────────────────────────
        'ViewAny:Ingredient',
        'View:Ingredient',
        'Create:Ingredient',
        'Update:Ingredient',
        'Delete:Ingredient',
        'DeleteAny:Ingredient',
        'Restore:Ingredient',
        'RestoreAny:Ingredient',
        'ForceDelete:Ingredient',
        'ForceDeleteAny:Ingredient',
        'Replicate:Ingredient',
        'Reorder:Ingredient',

        // ── Unit ──────────────────────────────────────────────
        'ViewAny:Unit',
        'View:Unit',
        'Create:Unit',
        'Update:Unit',
        'Delete:Unit',
        'DeleteAny:Unit',
        'Restore:Unit',
        'RestoreAny:Unit',
        'ForceDelete:Unit',
        'ForceDeleteAny:Unit',
        'Replicate:Unit',
        'Reorder:Unit',
    ];
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach ($this->permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission, 'guard_name' => 'web']
            );
        }
    }
}
