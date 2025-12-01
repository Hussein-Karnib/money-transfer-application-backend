<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Exchange_Rate extends Model
{
    // Explicitly set table name to match migration
    protected $table = 'exchange_rates';
    
    protected $fillable = [
        'currency_from','currency_to','rate','last_updated',
    ];

    protected $casts = [
        'last_updated' => 'datetime',
    ];

    public function currencyFrom()
    {
        return $this->belongsTo(Currency::class, 'currency_from', 'code');
    }

    public function currencyTo()
    {
        return $this->belongsTo(Currency::class, 'currency_to', 'code');
    }
}
