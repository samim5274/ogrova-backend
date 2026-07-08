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
        Schema::create('shipping_zones', function (Blueprint $table) {
            $table->id();

            // Location
            $table->foreignId('division_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('district_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('upazila_id')->nullable()->constrained()->nullOnDelete();

            // Zone Name
            $table->string('name')->nullable();

            // Shipping Charge
            $table->decimal('delivery_charge', 10, 2)->default(0);

            // Extra COD Charge
            $table->decimal('cod_charge', 10, 2)->default(0);

            // Free Shipping
            $table->boolean('free_shipping')->default(false);

            // Free Shipping Above Amount
            $table->decimal('free_shipping_amount', 10, 2)->nullable();

            // Weight Limit (KG)
            $table->decimal('max_weight', 8, 2)->nullable();

            // Estimated Delivery
            $table->unsignedTinyInteger('min_delivery_days')->default(1);
            $table->unsignedTinyInteger('max_delivery_days')->default(3);

            // Cash On Delivery
            $table->boolean('cod_available')->default(true);

            // Status
            $table->boolean('is_active')->default(true);

            // Priority (Specific rule first)
            $table->unsignedInteger('priority')->default(1);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipping_zones');
    }
};
