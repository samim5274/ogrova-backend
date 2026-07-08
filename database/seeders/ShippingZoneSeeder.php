<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\District;
use App\Models\Division;
use App\Models\ShippingZone;

class ShippingZoneSeeder extends Seeder
{
    public function run(): void
    {
        ShippingZone::query()->delete();

        // Dhaka Division
        $dhakaDivision = Division::where('name', 'Dhaka')->first();

        if (!$dhakaDivision) {
            $this->command->warn('Dhaka division not found.');
            return;
        }

        // Dhaka District
        $dhakaDistrict = District::where('division_id', $dhakaDivision->id)
            ->where('name', 'Dhaka')
            ->first();

        // Dhaka District (Delivery Charge 60)
        if ($dhakaDistrict) {
            ShippingZone::create([
                'division_id'          => $dhakaDivision->id,
                'district_id'          => $dhakaDistrict->id,
                'upazila_id'           => null,
                'name'                 => 'Dhaka City',
                'delivery_charge'      => 60,
                'cod_charge'           => 0,
                'free_shipping'        => false,
                'free_shipping_amount' => null,
                'max_weight'           => null,
                'min_delivery_days'    => 1,
                'max_delivery_days'    => 2,
                'cod_available'        => true,
                'priority'             => 100,
                'is_active'            => true,
            ]);
        }

        // All Other Districts (Delivery Charge 120)
        District::where('id', '!=', optional($dhakaDistrict)->id)
            ->orderBy('division_id')
            ->orderBy('name')
            ->get()
            ->each(function ($district) {

                ShippingZone::create([
                    'division_id'          => $district->division_id,
                    'district_id'          => $district->id,
                    'upazila_id'           => null,
                    'name'                 => $district->name,
                    'delivery_charge'      => 120,
                    'cod_charge'           => 0,
                    'free_shipping'        => false,
                    'free_shipping_amount' => null,
                    'max_weight'           => null,
                    'min_delivery_days'    => 2,
                    'max_delivery_days'    => 4,
                    'cod_available'        => true,
                    'priority'             => 50,
                    'is_active'            => true,
                ]);
            });
    }
}
