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
        Schema::create('customer_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            // Address Label
            $table->string('label')->default('Home');

            // Receiver Information
            $table->string('recipient_name');
            $table->string('phone', 20);

            // Location
            $table->foreignId('division_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('district_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('upazila_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->foreignId('police_station_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            // Address
            $table->text('address');

            $table->string('postal_code', 20)->nullable();

            // Default Address
            $table->boolean('is_default')->default(false);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_addresses');
    }
};
