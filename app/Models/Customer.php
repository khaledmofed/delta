<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    protected $fillable = [
        'name', 'name_ar', 'email', 'mobile', 'emirates_id', 'emirates_id_expiry', 'dob', 'gender',
        'nationality', 'emirate', 'license_no', 'license_from', 'license_to',
    ];

    protected $hidden = ['password', 'emirates_id', 'license_no'];

    protected function casts(): array
    {
        return [
            'emirates_id' => 'encrypted',
            'license_no' => 'encrypted',
            'emirates_id_expiry' => 'date',
            'dob' => 'date',
            'license_from' => 'date',
            'license_to' => 'date',
            'password' => 'hashed',
        ];
    }

    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
