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
        Schema::create('order_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Payment Source
            |--------------------------------------------------------------------------
            */

            $table->enum('payment_method',[
                'cod',
                'bank_transfer',
                'mobile_banking',
                'card',
                'paypal',
            ]);

            $table->string('gateway')->nullable(); // manual, sslcommerz ,stripe, bkash, nagad, rocket

            /*
            |--------------------------------------------------------------------------
            | Transaction
            |--------------------------------------------------------------------------
            */

            $table->string('transaction_id')->nullable()->index();
            $table->string('gateway_transaction_id')->nullable()->index();
            $table->string('gateway_payment_id')->nullable();
            $table->string('gateway_order_id')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Amount
            |--------------------------------------------------------------------------
            */

            $table->decimal('amount',12,2);
            $table->char('currency',3)->default('BDT');
            /*
            |--------------------------------------------------------------------------
            | Manual Payment
            |--------------------------------------------------------------------------
            */

            $table->string('bank_name')->nullable();
            $table->string('account_number')->nullable();
            $table->string('account_holder_name')->nullable();
            $table->string('sender_mobile')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Gateway Response
            |--------------------------------------------------------------------------
            */

            $table->longText('gateway_response')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Status
            |--------------------------------------------------------------------------
            */

            $table->enum('status',[
                'Pending',
                'Processing',
                'Success',
                'Failed',
                'Cancelled',
                'Refunded'
            ])->default('Pending');

            $table->timestamp('paid_at')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Verification
            |--------------------------------------------------------------------------
            */

            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->text('remarks')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_payments');
    }
};

/*
|--------------------------------------------------------------------------
| Payment Method vs Gateway
|--------------------------------------------------------------------------
|
| payment_method = How the customer paid.
| gateway        = Which provider/system processed the payment.
|
| -------------------------------------------------------------------------
| Manual Cash On Delivery (COD)
| -------------------------------------------------------------------------
| payment_method = cod
| gateway        = null
|
| -------------------------------------------------------------------------
| Manual Mobile Banking
| -------------------------------------------------------------------------
| payment_method = mobile_banking
| gateway        = manual
|
| Example:
| bKash (Manual)
| Nagad (Manual)
| Rocket (Manual)
|
| Customer manually transfers money and submits the transaction ID.
| Admin verifies the payment manually.
|
| -------------------------------------------------------------------------
| Manual Bank Transfer
| -------------------------------------------------------------------------
| payment_method = bank_transfer
| gateway        = manual
|
| Customer deposits/transfers money to the company's bank account.
| Admin verifies the payment manually.
|
| -------------------------------------------------------------------------
| SSLCommerz
| -------------------------------------------------------------------------
| payment_method = card
| gateway        = sslcommerz
|
| SSLCommerz processes Card, bKash, Nagad, Rocket, etc.
| Gateway automatically verifies the payment.
|
| -------------------------------------------------------------------------
| Stripe
| -------------------------------------------------------------------------
| payment_method = card
| gateway        = stripe
|
| Stripe processes Visa, MasterCard, Apple Pay, Google Pay, etc.
|
| -------------------------------------------------------------------------
| PayPal
| -------------------------------------------------------------------------
| payment_method = paypal
| gateway        = paypal
|
| Payment is processed directly by PayPal.
|
*/
