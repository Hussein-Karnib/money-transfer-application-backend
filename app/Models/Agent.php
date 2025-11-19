<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Agent extends Model
{
    protected $fillable = [
        'user_id','store_name','address','latitude','longitude','status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function hours()
    {
        return $this->hasMany(Agent_Hour::class);
    }

    public function transactions()
    {
        return $this->hasMany(Agent_Transaction::class);
    }
}
