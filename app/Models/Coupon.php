<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Coupon extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
    ];

    public function orders()
    {
        return $this->hasMany(Order::class, 'coupon_id');
    }

    public function usages()
    {
        return $this->hasMany(CouponUsage::class);
    }

    public function products()
    {
        return $this->belongsToMany(
            Product::class,
            'coupon_products'
        );
    }

    public function categories()
    {
        return $this->belongsToMany(
            ProductCategory::class,
            'coupon_categories',
            'coupon_id',
            'category_id'
        );
    }

    public function brands()
    {
        return $this->belongsToMany(
            Brand::class,
            'coupon_brands'
        );
    }

    public function vendors()
    {
        return $this->belongsToMany(
            Vendor::class,
            'coupon_vendors'
        );
    }
}
