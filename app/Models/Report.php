<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    protected $fillable = [
        'type','generated_at','generated_by','file_path','parameters',
    ];

    protected $casts = [
        'generated_at' => 'datetime',
        'parameters'   => 'array',
    ];

    public function author()
    {
        return $this->belongsTo(User::class, 'generated_by');
    }
}
