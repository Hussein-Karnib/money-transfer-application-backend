<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Agent extends Model
{
    protected $fillable = [
        'user_id','store_name','address','latitude','longitude','status','commission_rate',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function hours()
    {
        return $this->hasMany(Agent_Hour::class)->orderBy('day_of_week');
    }

    public function transactions()
    {
        return $this->hasMany(Agent_Transaction::class);
    }

    /**
     * Get total commission earned by this agent.
     */
    public function getTotalCommissionAttribute()
    {
        return $this->transactions()->sum('commission');
    }

    /**
     * Get commission for a specific period.
     */
    public function getCommissionForPeriod($startDate, $endDate)
    {
        return $this->transactions()
            ->whereBetween('processed_at', [$startDate, $endDate])
            ->sum('commission');
    }

    /**
     * Check if agent is currently open based on working hours.
     */
    public function isOpenNow()
    {
        $currentDay = now()->dayOfWeek;
        $currentTime = now()->format('H:i:s');

        return $this->hours()
            ->where('day_of_week', $currentDay)
            ->where('is_closed', false)
            ->where('open_time', '<=', $currentTime)
            ->where('close_time', '>=', $currentTime)
            ->exists();
    }
}
