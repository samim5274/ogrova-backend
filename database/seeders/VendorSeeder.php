<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Vendor;

class VendorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Vendor::updateOrCreate(
            ['email' => 'shop1@gmail.com'],
            [
                'shop_name' => 'Ogrova',
                'shop_slug' => 'ogrova-bazar',
                'phone' => '01533021557',
                'vendor_status' => 'approved',
                'is_active' => true,
            ]
        );

        Vendor::updateOrCreate(
            ['email' => 'shop2@gmail.com'],
            [
                'shop_name' => 'Smart Shop',
                'shop_slug' => 'smart-shop',
                'phone' => '01811111112',
                'vendor_status' => 'approved',
                'is_active' => true,
            ]
        );
    }
}
