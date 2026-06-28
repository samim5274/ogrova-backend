<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Super Admin
        User::updateOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'name' => 'Super Admin',
                'user_id' => 'SA-0001',
                'phone' => '01711111111',
                'password' => Hash::make('password'),
                'role' => 'super_admin',
                'vendors_id' => null,
                'is_active' => true,
            ]
        );

        // Admin 1
        User::updateOrCreate(
            ['email' => 'admin1@gmail.com'],
            [
                'name' => 'Admin One',
                'user_id' => 'AD-0001',
                'phone' => '01711111112',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'vendors_id' => null,
                'is_active' => true,
            ]
        );

        // Admin 2
        User::updateOrCreate(
            ['email' => 'admin2@gmail.com'],
            [
                'name' => 'Admin Two',
                'user_id' => 'AD-0002',
                'phone' => '01711111113',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'vendors_id' => null,
                'is_active' => true,
            ]
        );

        // Vendor Owner 1
        User::updateOrCreate(
            ['email' => 'vendor1@gmail.com'],
            [
                'name' => 'Vendor Owner One',
                'user_id' => 'VD-0001',
                'phone' => '01711111114',
                'password' => Hash::make('password'),
                'role' => 'vendor',
                'vendors_id' => 1,
                'is_active' => true,
            ]
        );

        // Vendor Owner 2
        User::updateOrCreate(
            ['email' => 'vendor2@gmail.com'],
            [
                'name' => 'Vendor Owner Two',
                'user_id' => 'VD-0002',
                'phone' => '01711111115',
                'password' => Hash::make('password'),
                'role' => 'vendor',
                'vendors_id' => 2,
                'is_active' => true,
            ]
        );

        // Customer 1
        User::updateOrCreate(
            ['email' => 'customer1@gmail.com'],
            [
                'name' => 'Customer One',
                'user_id' => 'CU-0001',
                'phone' => '01711111116',
                'password' => Hash::make('password'),
                'role' => 'customer',
                'vendors_id' => null,
                'is_active' => true,
            ]
        );

        // Customer 2
        User::updateOrCreate(
            ['email' => 'customer2@gmail.com'],
            [
                'name' => 'Customer Two',
                'user_id' => 'CU-0002',
                'phone' => '01711111117',
                'password' => Hash::make('password'),
                'role' => 'customer',
                'vendors_id' => null,
                'is_active' => true,
            ]
        );

        // Customer 3
        User::updateOrCreate(
            ['email' => 'customer3@gmail.com'],
            [
                'name' => 'Customer Three',
                'user_id' => 'CU-0003',
                'phone' => '01711111118',
                'password' => Hash::make('password'),
                'role' => 'customer',
                'vendors_id' => null,
                'is_active' => true,
            ]
        );
    }
}
