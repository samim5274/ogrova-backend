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
        Schema::create('coupon_usages', function (Blueprint $table) {
            $table->id();

            $table->foreignId('coupon_id')->constrained()->onDelete('restrict');

            $table->foreignId('user_id')->constrained()->onDelete('restrict');

            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();

            $table->decimal('discount_amount',10,2);

            $table->decimal('order_amount',10,2);

            $table->string('coupon_code');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coupon_usages');
    }
};
