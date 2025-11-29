<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class User_Verification extends Model
{
    protected $fillable = [
        'user_id',
        'id_type',
        'id_number',
        'document_path',
        'status',
        'verified_at',
    ];

    protected $casts = [
        'verified_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
