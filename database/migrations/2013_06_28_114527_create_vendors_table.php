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
        Schema::create('vendors', function (Blueprint $table) {
            $table->id();

            $table->string('shop_name');
            $table->string('shop_slug')->unique();

            $table->string('shop_logo')->nullable();
            $table->text('shop_description')->nullable();

            $table->enum('vendor_status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->boolean('is_active')->default(true);

            $table->decimal('wallet_balance', 12, 2)->default(0);
            $table->decimal('commission_rate', 5, 2)->default(0);

            $table->string('tax_id')->nullable();
            $table->string('business_license')->nullable();
            $table->string('business_document')->nullable();

            $table->string('email')->unique();
            $table->string('phone', 20)->unique();

            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('country')->nullable();
            $table->string('postal_code', 20)->nullable();

            $table->boolean('featured')->default(false);
            $table->decimal('rating', 3, 2)->default(0.00);
            $table->unsignedInteger('total_products')->default(0);

            $table->string('cover_image')->nullable();
            $table->string('website')->nullable();
            $table->string('facebook')->nullable();
            $table->string('instagram')->nullable();
            $table->string('youtube')->nullable();
            $table->string('whatsapp')->nullable();
            $table->time('opening_time')->nullable();
            $table->time('closing_time')->nullable();
            $table->boolean('is_verified')->default(false);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendors');
    }
};
