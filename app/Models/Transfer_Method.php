<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Database\Factories\TransferMethodFactory;

class Transfer_Method extends Model
{
    use HasFactory;

    // Explicitly set table name to match migration
    protected $table = 'transfer_methods';
    
    protected $fillable = ['name','description'];

    public function beneficiaries()
    {
        return $this->hasMany(Beneficiary::class);
    }

    protected static function newFactory()
    {
        return TransferMethodFactory::new();
    }
}
