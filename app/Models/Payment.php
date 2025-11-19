<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'transfer_id','amount','currency_code',
        'gateway','gateway_ref','status',
        'authorized_at','captured_at','refunded_at',
    ];

    protected $casts = [
        'authorized_at' => 'datetime',
        'captured_at'   => 'datetime',
        'refunded_at'   => 'datetime',
    ];

    public function transfer()
    {
        return $this->belongsTo(Transfer::class);
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class, 'currency_code', 'code');
    }
}
