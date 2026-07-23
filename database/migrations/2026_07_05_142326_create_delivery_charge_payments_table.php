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
        Schema::create('delivery_charge_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->onDelete('restrict');
            $table->dateTime('payment_date');
            $table->enum('payment_method', [
                'bank',
                'mobile',
                'sslcommerz',
                'cash'
            ]);
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('currency')->default('BDT');
            $table->string('bank_name')->nullable();
            $table->string('branch_name')->nullable();
            $table->string('account_number')->nullable();
            $table->string('account_holder_name')->nullable();
            $table->string('mobile_number')->nullable();
            $table->string('transaction_id')->nullable()->unique();
            $table->string('reference_no')->nullable();

            $table->enum('payment_status', [
                'pending',
                'success',
                'return',
                'failed'
            ])->default('pending');

            $table->foreignId('paid_by')
                ->nullable()
                ->constrained('users')
                ->onDelete('restrict');

            $table->text('notes')->nullable();
            $table->string('attachment')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_charge_payments');
    }
};
