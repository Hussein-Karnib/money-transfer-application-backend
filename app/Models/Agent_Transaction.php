<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Agent_Transaction extends Model
{
    protected $fillable = [
        'agent_id','transfer_id','type','amount','commission','processed_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'commission' => 'decimal:2',
        'processed_at' => 'datetime',
    ];

    public function agent()
    {
        return $this->belongsTo(Agent::class);
    }

    public function transfer()
    {
        return $this->belongsTo(Transfer::class);
    }
}
