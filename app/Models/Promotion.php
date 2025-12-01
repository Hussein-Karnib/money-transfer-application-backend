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
        if (!$this->active) {
            return false;
        }

        $now = now();

        if ($this->starts_at && $now->lt($this->starts_at)) {
            return false;
        }

        if ($this->ends_at && $now->gt($this->ends_at)) {
            return false;
        }

        if ($this->usage_limit !== null && $this->used_count >= $this->usage_limit) {
            return false;
        }

        if ($amount < (float) $this->min_amount) {
            return false;
        }

        if ($this->country_to_id && $countryToId && $this->country_to_id !== $countryToId) {
            return false;
        }

        return true;
    }
}


