<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Agent_Hour extends Model
{
    public $timestamps = false;
    protected $primaryKey = null;
    public $incrementing = false;

    protected $fillable = [
        'agent_id',
        'day_of_week',
        'open_time',
        'close_time',
        'is_closed',
    ];

    protected $casts = [
        'day_of_week' => 'integer',
        'open_time' => 'string',
        'close_time' => 'string',
        'is_closed' => 'boolean',
    ];

    public function agent()
    {
        return $this->belongsTo(Agent::class);
    }
}

