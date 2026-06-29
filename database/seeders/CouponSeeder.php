<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

use App\Models\Brand;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Vendor;

class CouponSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // পরিষ্কার করার জন্য (optional)
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        DB::table('coupon_products')->truncate();
        DB::table('coupon_categories')->truncate();
        DB::table('coupon_brands')->truncate();
        DB::table('coupon_vendors')->truncate();
        DB::table('coupon_usages')->truncate();
        DB::table('coupons')->truncate();

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        /*
        |--------------------------------------------------------------------------
        | Order Coupon
        |--------------------------------------------------------------------------
        */

        Coupon::create([
            'code' => 'SAVE100',
            'name' => 'Flat 100 Tk Off',
            'discount_type' => 'fixed',
            'discount' => 100,
            'minimum_order_amount' => 1000,
            'maximum_discount_amount' => null,
            'usage_limit' => 100,
            'usage_limit_per_user' => 2,
            'used_count' => 0,
            'start_date' => now()->subDay(),
            'end_date' => now()->addMonth(),
            'is_active' => true,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Product Coupon
        |--------------------------------------------------------------------------
        */

        if ($product = Product::first()) {

            $coupon = Coupon::create([
                'code' => 'PRODUCT20',
                'name' => '20% Product Discount',
                'discount_type' => 'percent',
                'discount' => 20,
                'minimum_order_amount' => 0,
                'maximum_discount_amount' => 500,
                'usage_limit' => 50,
                'usage_limit_per_user' => 1,
                'used_count' => 0,
                'start_date' => now()->subDay(),
                'end_date' => now()->addDays(15),
                'is_active' => true,
            ]);

            $coupon->products()->attach($product->id);
        }

        /*
        |--------------------------------------------------------------------------
        | Category Coupon
        |--------------------------------------------------------------------------
        */

        if ($category = ProductCategory::first()) {

            $coupon = Coupon::create([
                'code' => 'CATEGORY15',
                'name' => '15% Category Discount',
                'discount_type' => 'percent',
                'discount' => 15,
                'minimum_order_amount' => 500,
                'maximum_discount_amount' => 300,
                'usage_limit' => null,
                'usage_limit_per_user' => 5,
                'used_count' => 0,
                'start_date' => now()->subDay(),
                'end_date' => now()->addDays(20),
                'is_active' => true,
            ]);

            $coupon->categories()->attach($category->id);
        }

        /*
        |--------------------------------------------------------------------------
        | Brand Coupon
        |--------------------------------------------------------------------------
        */

        if ($brand = Brand::first()) {

            $coupon = Coupon::create([
                'code' => 'BRAND10',
                'name' => 'Brand Offer',
                'discount_type' => 'percent',
                'discount' => 10,
                'minimum_order_amount' => 300,
                'maximum_discount_amount' => 200,
                'usage_limit' => 100,
                'usage_limit_per_user' => 3,
                'used_count' => 0,
                'start_date' => now()->subDay(),
                'end_date' => now()->addDays(10),
                'is_active' => true,
            ]);

            $coupon->brands()->attach($brand->id);
        }

        /*
        |--------------------------------------------------------------------------
        | Vendor Coupon
        |--------------------------------------------------------------------------
        */

        if ($vendor = Vendor::first()) {

            $coupon = Coupon::create([
                'code' => 'VENDOR50',
                'name' => 'Vendor Offer',
                'discount_type' => 'fixed',
                'discount' => 50,
                'minimum_order_amount' => 400,
                'maximum_discount_amount' => null,
                'usage_limit' => 30,
                'usage_limit_per_user' => 2,
                'used_count' => 0,
                'start_date' => now()->subDay(),
                'end_date' => now()->addDays(30),
                'is_active' => true,
            ]);

            $coupon->vendors()->attach($vendor->id);
        }

        /*
        |--------------------------------------------------------------------------
        | Expired Coupon
        |--------------------------------------------------------------------------
        */

        Coupon::create([
            'code' => 'EXPIRED50',
            'name' => 'Expired Coupon',
            'discount_type' => 'fixed',
            'discount' => 50,
            'minimum_order_amount' => 500,
            'maximum_discount_amount' => null,
            'usage_limit' => 100,
            'usage_limit_per_user' => 1,
            'used_count' => 0,
            'start_date' => now()->subDays(30),
            'end_date' => now()->subDay(),
            'is_active' => true,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Disabled Coupon
        |--------------------------------------------------------------------------
        */

        Coupon::create([
            'code' => 'DISABLED10',
            'name' => 'Disabled Coupon',
            'discount_type' => 'percent',
            'discount' => 10,
            'minimum_order_amount' => 0,
            'maximum_discount_amount' => 100,
            'usage_limit' => null,
            'usage_limit_per_user' => 1,
            'used_count' => 0,
            'start_date' => now()->subDay(),
            'end_date' => now()->addMonth(),
            'is_active' => false,
        ]);
    }
}
