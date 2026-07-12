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
                'cash',
                'bank_transfer',
                'mobile_banking',
                'card',
                'paypal',
                'wallet',
            ]);

            /*
            |--------------------------------------------------------------------------
            | Provider
            |--------------------------------------------------------------------------
            |
            | cash
            | manual
            | bank
            | bkash
            | nagad
            | rocket
            | sslcommerz
            | stripe
            | paypal
            |
            */
            $table->string('provider')->nullable();

            /*
            |-------------------------------------------------------------------------
            | payment_method | provider   |
            | -------------- | ---------- |
            | mobile_banking | bkash      |
            | mobile_banking | nagad      |
            | card           | stripe     |
            | card           | sslcommerz |
            | cod            | cash       |
            |-------------------------------------------------------------------------
            */

            $table->enum('payment_type',[
                'Payment',
                'Refund',
                'Adjustment'
            ])->default('Payment');

            /*
            |---------------------------
            |   Cash = Offline
            |   Bank = Offline
            |   SSL = Online
            |   Stripe = Online
            |---------------------------
            */

            $table->enum('channel',[
                'Offline',
                'Online'
            ]);

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
            |
            | amount       = Customer paid amount
            |
            | gateway_fee  = Gateway processing fee
            |
            | net_amount   = amount - gateway_fee
            |
            */

            $table->decimal('amount',12,2);
            $table->decimal('gateway_fee',12,2)->default(0);
            $table->decimal('net_amount',12,2)->default(0);
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
            $table->string('sender_name')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Gateway Response
            |--------------------------------------------------------------------------
            */

            $table->json('gateway_response')->nullable();

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
            $table->text('failure_reason')->nullable();
            $table->timestamp('paid_at')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Verification
            |--------------------------------------------------------------------------
            */

            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reference',255)->nullable();
            $table->text('remarks')->nullable();

            /*
            |--------------------------------------------------------------------------
            | Security
            |--------------------------------------------------------------------------
            */

            $table->ipAddress('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->string('receipt_no')->nullable()->unique();

            $table->timestamps();

            /*
            |--------------------------------------------------------------------------
            | Indexing
            |--------------------------------------------------------------------------
            */

            $table->index(['order_id','status']);
            $table->index(['payment_method','status']);
            $table->index(['provider','status']);
            $table->index(['payment_type','status']);
            $table->index(['paid_at','status']);

            $table->index(['user_id','status']);
            $table->index(['user_id','created_at']);
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
| Payment Method vs provider
|--------------------------------------------------------------------------
|
| payment_method = How the customer paid.
| provider       = Which provider/system processed the payment.
|
| -------------------------------------------------------------------------
| Manual Cash On Delivery (COD)
| -------------------------------------------------------------------------
| payment_method = cod
| provider       = null
|
| -------------------------------------------------------------------------
| Manual Mobile Banking
| -------------------------------------------------------------------------
| payment_method = mobile_banking
| provider       = manual
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
| provider       = manual
|
| Customer deposits/transfers money to the company's bank account.
| Admin verifies the payment manually.
|
| -------------------------------------------------------------------------
| SSLCommerz
| -------------------------------------------------------------------------
| payment_method = card
| provider       = sslcommerz
|
| SSLCommerz processes Card, bKash, Nagad, Rocket, etc.
| provider automatically verifies the payment.
|
| -------------------------------------------------------------------------
| Stripe
| -------------------------------------------------------------------------
| payment_method = card
| provider       = stripe
|
| Stripe processes Visa, MasterCard, Apple Pay, Google Pay, etc.
|
| -------------------------------------------------------------------------
| PayPal
| -------------------------------------------------------------------------
| payment_method = paypal
| provider       = paypal
|
| Payment is processed directly by PayPal.
|
*/
