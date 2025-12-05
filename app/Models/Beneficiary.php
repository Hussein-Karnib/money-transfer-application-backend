<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Beneficiary extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id','full_name','country_id','transfer_method_id','payout_details',
    ];

    protected $casts = [
        'payout_details' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function method()
    {
        return $this->belongsTo(Transfer_Method::class, 'transfer_method_id');
    }

    public function transfers()
    {
        return $this->hasMany(Transfer::class);
    }
}
