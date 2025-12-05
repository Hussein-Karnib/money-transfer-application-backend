<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transfer extends Model
{
    use HasFactory;

    protected $fillable = [
        'sender_id',
        'beneficiary_id',
        'amount',
        'currency_from',
        'currency_to',
        'exchange_rate',
        'fee',
        'total_amount',
        'status',
        'initiated_at',
        'completed_at',
        'reference',
        'promotion_id',
        'discount_amount',
        'speed',
        'estimated_delivery_at',
    ];

    protected $casts = [
        'initiated_at' => 'datetime',
        'completed_at' => 'datetime',
        'estimated_delivery_at' => 'datetime',
    ];

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function beneficiary()
    {
        return $this->belongsTo(Beneficiary::class);
    }

    public function events()
    {
        return $this->hasMany(Transfer_Event::class);
    }

    public function payment()
    {
        return $this->hasOne(Payment::class);
    }

    public function promotion()
    {
        return $this->belongsTo(Promotion::class);
    }

    public function currencyFrom()
    {
        return $this->belongsTo(Currency::class, 'currency_from', 'code');
    }

    public function currencyTo()
    {
        return $this->belongsTo(Currency::class, 'currency_to', 'code');
    }

    public function exchangeRateRecord()
    {
        return $this->hasOne(Exchange_Rate::class, 'currency_from', 'currency_from')
            ->whereColumn('exchange_rates.currency_to', 'transfers.currency_to');
    }

    /**
     * Business-friendly status name for external consumers.
     *
     * queued            -> initiated
     * paid              -> pending
     * in_progress       -> processing
     * available_for_pickup -> ready_for_pickup
     * completed         -> completed
     * failed / refunded -> failed
     * disputed          -> disputed
     */
    public function getBusinessStatusAttribute(): string
    {
        return match ($this->status) {
            'queued' => 'initiated',
            'paid' => 'pending',
            'in_progress' => 'processing',
            'available_for_pickup' => 'ready_for_pickup',
            'completed' => 'completed',
            'failed', 'refunded' => 'failed',
            default => $this->status,
        };
    }
}
