<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable, SoftDeletes;

    protected $fillable = [
        'role_id', 'name', 'email', 'password', 'phone', 'status',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function verifications()
    {
        return $this->hasMany(User_Verification::class);
    }

    public function bankAccounts()
    {
        return $this->hasMany(User_BankAccount::class);
    }

    public function beneficiaries()
    {
        return $this->hasMany(Beneficiary::class);
    }

    public function transfersSent()
    {
        return $this->hasMany(Transfer::class, 'sender_id');
    }

    public function agent()
    {
        return $this->hasOne(Agent::class);
    }

    public function admin()
    {
        return $this->hasOne(Admin::class);
    }

    public function reports()
    {
        return $this->hasMany(Report::class, 'generated_by');
    }
}
