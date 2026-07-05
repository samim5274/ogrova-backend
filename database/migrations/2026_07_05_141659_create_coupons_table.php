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
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();             // SAVE10, EID50

            $table->string('name')->nullable();           // Eid Offer

            $table->enum('discount_type', ['fixed', 'percent']);

            $table->decimal('discount', 10, 2);

            $table->decimal('minimum_order_amount', 10, 2)->default(0);

            $table->decimal('maximum_discount_amount', 10, 2)->nullable();

            $table->integer('usage_limit')->nullable();          // Total usage limit

            $table->integer('usage_limit_per_user')->default(1);

            $table->integer('used_count')->default(0);

            $table->dateTime('start_date')->nullable();

            $table->dateTime('end_date')->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }
};
