<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderPayment extends Model
{
    use HasFactory;

    /*
    |--------------------------------------------------------------------------
    | Payment Methods
    |--------------------------------------------------------------------------
    */

    public const METHOD_COD             = 'cod';
    public const METHOD_BANK_TRANSFER   = 'bank_transfer';
    public const METHOD_MOBILE_BANKING  = 'mobile_banking';
    public const METHOD_SSLCOMMERZ      = 'sslcommerz';
    public const METHOD_STRIPE          = 'stripe';
    public const METHOD_PAYPAL          = 'paypal';
    public const METHOD_MANUAL          = 'manual';

    /*
    |--------------------------------------------------------------------------
    | Payment Status
    |--------------------------------------------------------------------------
    */

    public const STATUS_PENDING   = 'Pending';
    public const STATUS_PROCESSING = 'Processing';
    public const STATUS_SUCCESS   = 'Success';
    public const STATUS_FAILED    = 'Failed';
    public const STATUS_CANCELLED = 'Cancelled';
    public const STATUS_REFUNDED  = 'Refunded';

    /*
    |--------------------------------------------------------------------------
    | Fillable
    |--------------------------------------------------------------------------
    */

    protected $fillable = [

        'order_id',
        'user_id',

        'payment_method',
        'gateway',

        'transaction_id',
        'gateway_transaction_id',
        'gateway_payment_id',
        'gateway_order_id',

        'amount',
        'currency',

        'bank_name',
        'account_number',
        'account_holder_name',
        'sender_mobile',

        'gateway_response',

        'status',
        'paid_at',

        'verified_by',
        'verified_at',

        'remarks',
    ];

    /*
    |--------------------------------------------------------------------------
    | Casts
    |--------------------------------------------------------------------------
    */

    protected $casts = [

        'amount' => 'decimal:2',

        'paid_at' => 'datetime',

        'verified_at' => 'datetime',

        'gateway_response' => 'array',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeSuccess($query)
    {
        return $query->where('status', self::STATUS_SUCCESS);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function isPaid(): bool
    {
        return $this->status === self::STATUS_SUCCESS;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }
}
