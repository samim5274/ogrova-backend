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

            // 1. Identification & Routing
            $table->string('reg')->unique();
            $table->string('slug')->unique();
            $table->date('date')->index();

            // 2. Relationship
            $table->foreignId('user_id')->constrained('users')->onDelete('restrict');

            // 3. Coupon
            $table->unsignedBigInteger('coupon_id')->nullable()->index();

            $table->string('coupon_code')->nullable();

            // 4. Financial Data (Money Matters)
            $table->decimal('amount', 12, 2)->default(0);
            $table->decimal('coupon_discount', 12, 2)->default(0);
            $table->decimal('shipping_charge', 12, 2)->default(0);
            $table->decimal('tax', 12, 2)->default(0);
            $table->decimal('discount', 12, 2)->default(0.00);
            $table->decimal('payable_amount', 12, 2)->default(0.00);
            $table->string('currency', 20)->nullable()->default("BDT");
            $table->integer('point')->default(0);

            // 5. Payment Information
            $table->string('payment_method')->nullable();
            $table->string('transaction_id')->nullable()->unique();
            $table->enum('payment_status', [
                'Pending',
                'Paid',
                'Failed',
                'Refunded'
            ])->default('Pending');
            $table->timestamp('paid_at')->nullable();

            // 6. Order Status & Tracking
            $table->enum('status', [
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

            // 7. Shipping
            $table->string('contact_name');
            $table->string('contact_number')->nullable();
            $table->string('contact_email')->nullable();
            $table->text('shipping_address')->nullable();
            $table->string('remarks')->nullable();

            // 8. Tracking Timestamps (For Analytics & UI Timeline)
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            $table->softDeletes();
            $table->timestamps();
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
