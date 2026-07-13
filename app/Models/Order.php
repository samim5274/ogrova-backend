<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Order extends Model
{
    use HasFactory, SoftDeletes;

    public const PAYMENT_METHOD_COD='cod';
    public const PAYMENT_METHOD_ONLINE='advance';

    public const CURRENCY_BDT = 'BDT';

    public const PAYMENT_PENDING='Pending';
    public const PAYMENT_PARTIAL='Partial';
    public const PAYMENT_PAID='Paid';
    public const PAYMENT_FAILED='Failed';
    public const PAYMENT_REFUNDED='Refunded';

    public const STATUS_PENDING='Pending';
    public const STATUS_CONFIRMED='Confirmed';
    public const STATUS_PROCESSING='Processing';
    public const STATUS_PICKED='Picked';
    public const STATUS_SHIPPED='Shipped';
    public const STATUS_OUT_FOR_DELIVERY='Out for Delivery';
    public const STATUS_DELIVERED='Delivered';
    public const STATUS_CANCELLED='Cancelled';
    public const STATUS_FAILED='Failed';
    public const STATUS_RETURNED='Returned';

    protected $fillable = [
        /*
        |--------------------------------------------------------------------------
        | Identification
        |--------------------------------------------------------------------------
        */
        'reg',
        'date',

        /*
        |--------------------------------------------------------------------------
        | Relationship
        |--------------------------------------------------------------------------
        */
        'user_id',
        'coupon_id',
        'coupon_code',

        /*
        |--------------------------------------------------------------------------
        | Financial
        |--------------------------------------------------------------------------
        */
        'amount',
        'coupon_discount',
        'shipping_charge',
        'tax',
        'discount',
        'payable_amount',
        'paid_amount',
        'due_amount',
        'currency',
        'point',

        /*
        |--------------------------------------------------------------------------
        | Payment
        |--------------------------------------------------------------------------
        */
        'payment_method',
        'payment_status',
        'paid_at',
        'submitted_at',

        /*
        |--------------------------------------------------------------------------
        | Order Status
        |--------------------------------------------------------------------------
        */
        'status',
        'referral_bonus_paid',

        /*
        |--------------------------------------------------------------------------
        | Shipping
        |--------------------------------------------------------------------------
        */
        'contact_name',
        'contact_number',
        'contact_email',
        'shipping_address',

        'division_id',
        'district_id',
        'upazila_id',
        'police_station_id',
        'postal_code',

        'remarks',

        /*
        |--------------------------------------------------------------------------
        | Timeline
        |--------------------------------------------------------------------------
        */
        'processing_at',
        'picked_at',
        'confirmed_at',
        'shipped_at',
        'delivered_at',
        'cancelled_at',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'date' => 'date',

        'amount' => 'decimal:2',
        'coupon_discount' => 'decimal:2',
        'shipping_charge' => 'decimal:2',
        'tax' => 'decimal:2',
        'discount' => 'decimal:2',
        'payable_amount' => 'decimal:2',

        'point' => 'integer',

        'referral_bonus_paid' => 'boolean',

        'processing_at' => 'datetime',
        'picked_at' => 'datetime',
        'paid_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'shipped_at' => 'datetime',
        'delivered_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    protected $attributes = [
        'currency'=>'BDT',
        'payment_method'=>self::PAYMENT_METHOD_COD,
        'payment_status'=>self::PAYMENT_PENDING,
        'status'=>self::STATUS_PENDING,
    ];

    public const PAYMENT_METHODS = [
        self::PAYMENT_METHOD_COD,
        self::PAYMENT_METHOD_ONLINE,
    ];

    public const PAYMENT_STATUSES = [
        self::PAYMENT_PENDING,
        self::PAYMENT_PARTIAL,
        self::PAYMENT_PAID,
        self::PAYMENT_FAILED,
        self::PAYMENT_REFUNDED,
    ];

    public const ORDER_STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_CONFIRMED,
        self::STATUS_PROCESSING,
        self::STATUS_PICKED,
        self::STATUS_SHIPPED,
        self::STATUS_OUT_FOR_DELIVERY,
        self::STATUS_DELIVERED,
        self::STATUS_CANCELLED,
        self::STATUS_FAILED,
        self::STATUS_RETURNED,
    ];

    // Auto slug generate
    protected static function booted(): void
    {
        static::creating(function (Order $order) {
            if (blank($order->slug)) {
                $order->slug = static::generateSlug($order);
            }
        });
    }

    private static function generateSlug(self $order): string
    {
        do {
            $slug = Str::slug(
                'order-' . $order->reg . '-' . Str::random(8)
            );
        } while (static::where('slug', $slug)->exists());

        return $slug;
    }

    // Scope Status
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopePaid(Builder $query): Builder
    {
        return $query->where('payment_status', self::PAYMENT_PAID);
    }

    public function scopeDelivered(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_DELIVERED);
    }

    public function scopeConfirmed(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_CONFIRMED);
    }

    public function scopeProcessing(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PROCESSING);
    }

    public function scopeCancelled(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_CANCELLED);
    }

    // Helper Methods
    public function isPaid(): bool
    {
        return $this->payment_status===self::PAYMENT_PAID;
    }

    public function isPending(): bool
    {
        return $this->status===self::STATUS_PENDING;
    }

    public function isDelivered(): bool
    {
        return $this->status===self::STATUS_DELIVERED;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    public function isRefunded(): bool
    {
        return $this->payment_status === self::PAYMENT_REFUNDED;
    }

    public function isCod(): bool
    {
        return $this->payment_method === self::PAYMENT_METHOD_COD;
    }

    public function isOnline(): bool
    {
        return $this->payment_method === self::PAYMENT_METHOD_ONLINE;
    }

    public function getFormattedAmountAttribute(): string
    {
        return number_format($this->payable_amount, 2);
    }

    // Relation
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function deliveryCharges(): HasMany
    {
        return $this->hasMany(DeliveryChargePayment::class);
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function upazila(): BelongsTo
    {
        return $this->belongsTo(Upazila::class);
    }

    public function policeStation(): BelongsTo
    {
        return $this->belongsTo(PoliceStation::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(OrderPayment::class);
    }

    public function payment()
    {
        return $this->hasOne(OrderPayment::class);
    }
}
