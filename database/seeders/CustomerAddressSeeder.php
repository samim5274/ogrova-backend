<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\CustomerAddress;

class CustomerAddressSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        CustomerAddress::insert([
            [
                'user_id'            => 2,
                'label'              => 'Home',
                'recipient_name'     => 'Samim Hossain',
                'phone'              => '01712345678',

                'division_id'        => 1,
                'district_id'        => 1,
                'upazila_id'         => 1,
                'police_station_id'  => 1,

                'address'            => 'House #12, Road #5, Dhanmondi',
                'postal_code'        => '1209',

                'is_default'         => true,

                'created_at'         => now(),
                'updated_at'         => now(),
            ],
            [
                'user_id'            => 2,
                'label'              => 'Office',
                'recipient_name'     => 'Samim Hossain',
                'phone'              => '01812345678',

                'division_id'        => 1,
                'district_id'        => 1,
                'upazila_id'         => 2,
                'police_station_id'  => 2,

                'address'            => 'Level 5, Business Center, Banani',
                'postal_code'        => '1213',

                'is_default'         => false,

                'created_at'         => now(),
                'updated_at'         => now(),
            ],
        ]);
    }
}
