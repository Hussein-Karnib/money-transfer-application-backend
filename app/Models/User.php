<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;   
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;  

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'role_id',
        'status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
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
}
