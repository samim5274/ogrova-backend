<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductRating extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'user_id',
        'rating',
        'title',
        'review',
        'verified_purchase',
        'is_approved',
        'is_featured',
        'helpful_count',
        'unhelpful_count',
        'admin_note',
    ];

    protected $casts = [
        'rating'              => 'integer',
        'verified_purchase'   => 'boolean',
        'is_approved'         => 'boolean',
        'is_featured'         => 'boolean',
        'helpful_count'       => 'integer',
        'unhelpful_count'     => 'integer',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function images()
    {
        return $this->hasMany(ProductRatingImage::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeApproved($query)
    {
        return $query->where('is_approved', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeVerified($query)
    {
        return $query->where('verified_purchase', true);
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    public function getStarsAttribute()
    {
        return str_repeat('★', $this->rating);
    }
}
