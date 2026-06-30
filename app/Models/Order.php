<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Order extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        /*
        |--------------------------------------------------------------------------
        | Identification
        |--------------------------------------------------------------------------
        */
        'reg',
        'slug',
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
        'currency',
        'point',

        /*
        |--------------------------------------------------------------------------
        | Payment
        |--------------------------------------------------------------------------
        */
        'payment_method',
        'transaction_id',
        'payment_status',
        'paid_at',

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
        'remarks',

        /*
        |--------------------------------------------------------------------------
        | Timeline
        |--------------------------------------------------------------------------
        */
        'confirmed_at',
        'shipped_at',
        'delivered_at',
        'cancelled_at',
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

        'paid_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'shipped_at' => 'datetime',
        'delivered_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    // Auto slug generate
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($order) {
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

    // Relation
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function coupon()
    {
        return $this->belongsTo(Coupon::class, 'coupon_id');
    }

    public function delivaryCharge()
    {
        return $this->hasMany(DeliveryChargePayment::class);
    }
}
