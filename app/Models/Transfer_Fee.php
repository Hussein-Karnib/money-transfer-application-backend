<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transfer_Fee extends Model
{
    protected $fillable = [
        'country_from_id','country_to_id',
        'min_amount','max_amount',
        'fee_fixed','fee_percent','last_updated',
    ];

    protected $casts = [
        'last_updated' => 'datetime',
    ];

    public function countryFrom()
    {
        return $this->belongsTo(Country::class, 'country_from_id');
    }

    public function countryTo()
    {
        return $this->belongsTo(Country::class, 'country_to_id');
    }
}
