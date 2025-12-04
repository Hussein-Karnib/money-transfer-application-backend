<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Database\Factories\TransferFeeFactory;

class Transfer_Fee extends Model
{
    use HasFactory;

    // Explicitly set table name to match migration
    protected $table = 'transfer_fees';
    
    protected $fillable = [
        'country_from_id','country_to_id',
        'min_amount','max_amount',
        'fee_fixed','fee_percent','last_updated',
    ];

    protected $casts = [
        'last_updated' => 'datetime',
    ];

    public function countryFrom()
    {
        return $this->belongsTo(Country::class, 'country_from_id');
    }

    public function countryTo()
    {
        return $this->belongsTo(Country::class, 'country_to_id');
    }

    protected static function newFactory()
    {
        return TransferFeeFactory::new();
    }
}
