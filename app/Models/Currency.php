<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
    protected $primaryKey = 'code';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['code','name','decimals'];

    public function exchangeRatesFrom()
    {
        return $this->hasMany(Exchange_Rate::class, 'currency_from', 'code');
    }

    public function exchangeRatesTo()
    {
        return $this->hasMany(Exchange_Rate::class, 'currency_to', 'code');
    }
}
