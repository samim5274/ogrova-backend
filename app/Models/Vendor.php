<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vendor extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'shop_name',
        'shop_slug',
        'shop_logo',
        'shop_description',

        'vendor_status',
        'is_active',

        'wallet_balance',
        'commission_rate',

        'tax_id',
        'business_license',
        'business_document',

        'email',
        'phone',

        'address',
        'city',
        'state',
        'country',
        'postal_code',

        'featured',
        'rating',
        'total_products',

        'cover_image',
        'website',
        'facebook',
        'instagram',
        'youtube',
        'whatsapp',

        'opening_time',
        'closing_time',

        'is_verified',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'featured' => 'boolean',
        'is_verified' => 'boolean',

        'wallet_balance' => 'decimal:2',
        'commission_rate' => 'decimal:2',
        'rating' => 'decimal:2',

        'opening_time' => 'datetime:H:i',
        'closing_time' => 'datetime:H:i',
    ];

    public function user()
    {
        return $this->hasMany(User::class, 'vendors_id');
    }

}
