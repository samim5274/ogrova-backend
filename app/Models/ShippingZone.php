<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShippingZone extends Model
{
    use HasFactory;

    protected $fillable = [
        'division_id',
        'district_id',
        'upazila_id',
        'inside_charge',
        'outside_charge',
        'cod_charge',
        'estimated_days',
        'is_active',
    ];

    protected $casts = [
        'inside_charge' => 'decimal:2',
        'outside_charge' => 'decimal:2',
        'cod_charge' => 'decimal:2',
        'estimated_days' => 'integer',
        'is_active' => 'boolean',
    ];

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
}
