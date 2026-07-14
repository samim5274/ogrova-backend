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
        Schema::create('product_ratings', function (Blueprint $table) {
            $table->id();

            // Relations
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();

            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Rating
            $table->tinyInteger('rating'); // 1 - 5

            // Review
            $table->string('title')->nullable();
            $table->text('review')->nullable();

            // Status
            $table->boolean('verified_purchase')->default(false);
            $table->boolean('is_approved')->default(false);
            $table->boolean('is_featured')->default(false);

            // Statistics
            $table->unsignedInteger('helpful_count')->default(0);
            $table->unsignedInteger('unhelpful_count')->default(0);

            // Admin
            $table->text('admin_note')->nullable();

            $table->timestamps();

            // One user can review one product once
            $table->unique(['product_id', 'user_id']);

            // Index
            $table->index(['product_id', 'rating']);
            $table->index(['product_id', 'is_approved']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_ratings');
    }
};
