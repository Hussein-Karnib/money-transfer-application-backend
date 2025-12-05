<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Database\Factories\TransferEventFactory;

class Transfer_Event extends Model
{
    use HasFactory;

    // Explicitly set table name to match migration
    protected $table = 'transfer_events';
    
    protected $fillable = [
        'transfer_id','status','note','actor_type','actor_id',
    ];

    public function transfer()
    {
        return $this->belongsTo(Transfer::class);
    }

    protected static function newFactory()
    {
        return TransferEventFactory::new();
    }
}
