<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transfer_Event extends Model
{
    protected $fillable = [
        'transfer_id','status','note','actor_type','actor_id',
    ];

    public function transfer()
    {
        return $this->belongsTo(Transfer::class);
    }
}
