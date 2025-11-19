<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Audit_Log extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id','actor_type','actor_id','action',
        'table_name','record_id','metadata','created_at',
    ];

    protected $casts = [
        'metadata'   => 'array',
        'created_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
