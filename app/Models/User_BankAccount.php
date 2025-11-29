<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class User_BankAccount extends Model
{
    protected $fillable = [
        'user_id',
        'bank_name',
        'account_number',
        'currency_code',
        'is_default',
        'verified',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'verified'   => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class, 'currency_code', 'code');
    }
}
