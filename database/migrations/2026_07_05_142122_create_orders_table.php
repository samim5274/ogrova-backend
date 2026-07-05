<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            /*
            |--------------------------------------------------------------------------
            | Identification
            |--------------------------------------------------------------------------
            */

            $table->string('reg')->unique();
            $table->string('slug')->unique();
            $table->date('date')->index();

            /*
            |--------------------------------------------------------------------------
            | Relationship
            |--------------------------------------------------------------------------
            */

            $table->foreignId('user_id')
                ->constrained()
                ->restrictOnDelete()
                ->index();

            /*
            |--------------------------------------------------------------------------
            | Coupon
            |--------------------------------------------------------------------------
            */

            $table->foreignId('coupon_id')
                 ->nullable()
                ->constrained('coupons')
                ->nullOnDelete();

            $table->string('coupon_code')->nullable()->index();

            /*
            |--------------------------------------------------------------------------
            | Financial
            |--------------------------------------------------------------------------
            */

            $table->decimal('amount',12,2)->default(0);
            $table->decimal('coupon_discount',12,2)->default(0);
            $table->decimal('shipping_charge',12,2)->default(0);
            $table->decimal('tax',12,2)->default(0);
            $table->decimal('discount',12,2)->default(0);
            $table->decimal('payable_amount',12,2)->default(0);

            $table->char('currency',3)->default('BDT');

            $table->integer('point')->default(0);

            /*
            |--------------------------------------------------------------------------
            | Payment
            |--------------------------------------------------------------------------
            */

            $table->enum('payment_method',[
                'cod',
                'online'
            ])->default('cod')->index();

            $table->enum('payment_status',[
                'Pending',
                'Partial',
                'Paid',
                'Failed',
                'Refunded'
            ])->default('Pending')->index();

            $table->timestamp('paid_at')->nullable()->index();

            /*
            |--------------------------------------------------------------------------
            | Order Status
            |--------------------------------------------------------------------------
            */

            $table->enum('status',[
                'Pending',
                'Confirmed',
                'Processing',
                'Picked',
                'Shipped',
                'Out for Delivery',
                'Delivered',
                'Cancelled',
                'Failed',
                'Returned'
            ])->default('Pending')->index();

            $table->boolean('referral_bonus_paid')->default(false);

            /*
            |--------------------------------------------------------------------------
            | Shipping
            |--------------------------------------------------------------------------
            */

            $table->string('contact_name');

            $table->string('contact_number', 20);

            $table->string('contact_email')->nullable();

            $table->text('shipping_address');

            $table->foreignId('division_id')
                ->constrained('divisions')
                ->restrictOnDelete();

            $table->foreignId('district_id')
                ->constrained('districts')
                ->restrictOnDelete();

            $table->foreignId('upazila_id')
                ->nullable()
                ->constrained('upazilas')
                ->restrictOnDelete();

            $table->foreignId('police_station_id')
                ->nullable()
                ->constrained('police_stations')
                ->restrictOnDelete();

            $table->string('postal_code',20)->nullable();

            $table->text('remarks')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Timeline
            |--------------------------------------------------------------------------
            */

            $table->timestamp('processing_at')->nullable();

            $table->timestamp('picked_at')->nullable();

            $table->timestamp('confirmed_at')->nullable();

            $table->timestamp('shipped_at')->nullable();

            $table->timestamp('delivered_at')->nullable();

            $table->timestamp('cancelled_at')->nullable();

            $table->softDeletes();

            $table->timestamps();

            /*
            |--------------------------------------------------------------------------
            | Composite Index
            |--------------------------------------------------------------------------
            */

            $table->index(['user_id','status']);

            $table->index(['user_id','payment_status']);

            $table->index(['status','payment_status']);

            $table->index(['created_at','status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
