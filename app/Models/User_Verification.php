<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class User_Verification extends Model
{
    protected $fillable = [
        'user_id','id_type','id_number','status','verified_at','documents',
    ];

    protected $casts = [
        'verified_at' => 'datetime',
        'documents'   => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
