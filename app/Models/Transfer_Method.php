<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transfer_Method extends Model
{
    // Explicitly set table name to match migration
    protected $table = 'transfer_methods';
    
    protected $fillable = ['name','description'];

    public function beneficiaries()
    {
        return $this->hasMany(Beneficiary::class);
    }
}
