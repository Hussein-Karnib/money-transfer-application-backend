<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Promotion extends Model
{
    protected $fillable = [
        'code',
        'description',
        'discount_type',
        'discount_value',
        'max_discount',
        'min_amount',
        'country_to_id',
        'starts_at',
        'ends_at',
        'usage_limit',
        'used_count',
        'active',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'active' => 'boolean',
    ];

    public function countryTo()
    {
        return $this->belongsTo(Country::class, 'country_to_id');
    }

   
public function isValidFor(float $amount, ?int $countryToId = null): bool
{
    // 1) Must be active
    if (!$this->active) {
        return false;
    }

    // 2) Usage limit
    if ($this->usage_limit !== null && $this->used_count >= $this->usage_limit) {
        return false;
    }

    // 3) Minimum amount (remember: you're passing the FEE here)
    if ($amount < (float) $this->min_amount) {
        return false;
    }

    // 4) Country restriction: if promo is tied to a specific destination,
    //    the destinationCountryId MUST match.
    if ($this->country_to_id !== null) {
       
        if ($countryToId === null) {
            return false;
        }

        if ($this->country_to_id !== $countryToId) {
            return false;
        }
    }

    // (Optional: later you can add date window checks here again)

    return true;
}


   


}


