<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Agent_Hour extends Model
{
    public $timestamps = false;
    protected $primaryKey = null;
    public $incrementing = false;

    protected $fillable = ['agent_id','day_of_week','open_time','close_time'];

    public function agent()
    {
        return $this->belongsTo(Agent::class);
    }
}

