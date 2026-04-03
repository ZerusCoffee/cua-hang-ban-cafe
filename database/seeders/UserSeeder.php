<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    private array $fixedUsers = [
        [
            'name' => 'Super Admin',
            'email' => 'superadmin@example.com',
            'role' => 'super_admin',
        ],
        [
            'name' => 'Manager',
            'email' => 'manager@example.com',
            'role' => 'manager',
        ],
        [
            'name' => 'Sales',
            'email' => 'sales@example.com',
            'role' => 'sales',
        ],
        [
            'name' => 'Warehouse',
            'email' => 'warehouse@example.com',
            'role' => 'warehouse',
        ],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
         // Tạo tài khoản cố định cho từng role
        foreach ($this->fixedUsers as $data) {
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name'              => $data['name'],
                    'password'          => Hash::make('password'),
                    'email_verified_at' => now(),
                    'avatar'            => null,
                ]
            );
            $user->assignRole($data['role']);
        }

        // Tạo user ngẫu nhiên theo từng role
        $randomUsers = [
            'manager'   => 3,
            'sales'     => 10,
            'warehouse' => 5,
        ];

        foreach ($randomUsers as $role => $count) {
            User::factory()
                ->count($count)
                ->create()
                ->each(fn(User $user) => $user->syncRoles($role));
        }
    }
}
