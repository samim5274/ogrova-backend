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
            $table->foreignId('division_id')->nullable()->constrained()->nullOnDelete();

            $table->foreignId('district_id')->nullable()->constrained()->nullOnDelete();

            $table->foreignId('upazila_id')->nullable()->constrained()->nullOnDelete();

            // Delivery Charge
            $table->decimal('inside_charge', 10, 2)->default(0);
            $table->decimal('outside_charge', 10, 2)->default(0);

            // Extra COD Charge
            $table->decimal('cod_charge', 10, 2)->default(0);

            // Estimated Delivery Time
            $table->unsignedTinyInteger('estimated_days')->default(2);

            // Active/Inactive
            $table->boolean('is_active')->default(true);

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
