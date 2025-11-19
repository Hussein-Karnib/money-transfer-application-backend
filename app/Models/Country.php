<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    protected $fillable = ['iso2','name'];

    public function beneficiaries()
    {
        return $this->hasMany(Beneficiary::class);
    }
}
