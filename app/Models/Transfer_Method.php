<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transfer_Method extends Model
{
    protected $fillable = ['name','description'];

    public function beneficiaries()
    {
        return $this->hasMany(Beneficiary::class);
    }
}
