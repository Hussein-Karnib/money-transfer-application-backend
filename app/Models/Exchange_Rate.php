<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Exchange_Rate extends Model
{
    protected $fillable = [
        'currency_from','currency_to','rate','last_updated',
    ];

    protected $casts = [
        'last_updated' => 'datetime',
    ];
}
