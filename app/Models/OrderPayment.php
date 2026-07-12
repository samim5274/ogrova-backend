<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class OrderPayment extends Model
{
    use HasFactory;

    /*
    |--------------------------------------------------------------------------
    | Currency
    |--------------------------------------------------------------------------
    */

    public const CURRENCY_BDT = 'BDT';

    /*
    |--------------------------------------------------------------------------
    | Payment Methods
    |--------------------------------------------------------------------------
    */

    public const METHOD_COD             = 'cod';
    public const METHOD_CASH            = 'cash';
    public const METHOD_BANK_TRANSFER   = 'bank_transfer';
    public const METHOD_MOBILE_BANKING  = 'mobile_banking';
    public const METHOD_CARD            = 'card';
    public const METHOD_PAYPAL          = 'paypal';
    public const METHOD_WALLET          = 'wallet';

    /*
    |--------------------------------------------------------------------------
    | Payment Type
    |--------------------------------------------------------------------------
    */

    public const TYPE_PAYMENT     = 'Payment';
    public const TYPE_REFUND      = 'Refund';
    public const TYPE_ADJUSTMENT  = 'Adjustment';

    /*
    |--------------------------------------------------------------------------
    | Channel
    |--------------------------------------------------------------------------
    */

    public const CHANNEL_ONLINE  = 'Online';
    public const CHANNEL_OFFLINE = 'Offline';

    /*
    |--------------------------------------------------------------------------
    | Provider
    |--------------------------------------------------------------------------
    */

    public const PROVIDER_CASH         = 'cash';
    public const PROVIDER_MANUAL       = 'manual';
    public const PROVIDER_BANK         = 'bank';
    public const PROVIDER_BKASH        = 'bkash';
    public const PROVIDER_NAGAD        = 'nagad';
    public const PROVIDER_ROCKET       = 'rocket';
    public const PROVIDER_SSLCOMMERZ   = 'sslcommerz';
    public const PROVIDER_STRIPE       = 'stripe';
    public const PROVIDER_PAYPAL       = 'paypal';

    /*
    |--------------------------------------------------------------------------
    | Payment Status
    |--------------------------------------------------------------------------
    */

    public const STATUS_PENDING    = 'Pending';
    public const STATUS_PROCESSING = 'Processing';
    public const STATUS_SUCCESS    = 'Success';
    public const STATUS_FAILED     = 'Failed';
    public const STATUS_CANCELLED  = 'Cancelled';
    public const STATUS_REFUNDED   = 'Refunded';

    /*
    |--------------------------------------------------------------------------
    | Fillable
    |--------------------------------------------------------------------------
    */

    protected $fillable = [

        'order_id',
        'user_id',

        'payment_method',
        'provider',
        'payment_type',
        'channel',

        'transaction_id',
        'gateway_transaction_id',
        'gateway_payment_id',
        'gateway_order_id',
        'gateway_fee',

        'amount',
        'net_amount',
        'currency',

        'bank_name',
        'account_number',
        'account_holder_name',
        'sender_mobile',
        'sender_name',

        'gateway_response',

        'status',
        'failure_reason',
        'paid_at',

        'verified_by',
        'verified_at',
        'received_by',

        'reference',
        'remarks',

        'ip_address',
        'user_agent',
        'receipt_no',
    ];

    /*
    |--------------------------------------------------------------------------
    | Casts
    |--------------------------------------------------------------------------
    */

    protected $casts = [

        'amount' => 'decimal:2',
        'gateway_fee' => 'decimal:2',
        'net_amount' => 'decimal:2',

        'gateway_response' => 'array',

        'paid_at' => 'datetime',
        'verified_at' => 'datetime',

    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class,'received_by');
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopePending($query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeProcessing($query): Builder
    {
        return $query->where('status', self::STATUS_PROCESSING);
    }

    public function scopeSuccess($query): Builder
    {
        return $query->where('status', self::STATUS_SUCCESS);
    }

    public function scopeFailed($query): Builder
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    public function scopeRefund($query): Builder
    {
        return $query->where('payment_type', self::TYPE_REFUND);
    }

    public function scopeOnline($query): Builder
    {
        return $query->where('channel', self::CHANNEL_ONLINE);
    }

    public function scopeOffline($query): Builder
    {
        return $query->where('channel', self::CHANNEL_OFFLINE);
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

    public function isProcessing(): bool
    {
        return $this->status === self::STATUS_PROCESSING;
    }

    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    public function isRefund(): bool
    {
        return $this->payment_type === self::TYPE_REFUND;
    }

    public function isOnline(): bool
    {
        return $this->channel === self::CHANNEL_ONLINE;
    }

    public function isOffline(): bool
    {
        return $this->channel === self::CHANNEL_OFFLINE;
    }

    public function calculateNetAmount(): float
    {
        return (float) $this->amount - (float) $this->gateway_fee;
    }
}
