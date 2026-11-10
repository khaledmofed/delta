<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lead extends Model
{
    protected $fillable = ['reference', 'product', 'name', 'email', 'mobile', 'data', 'status', 'owner_id', 'next_action_at'];

    protected function casts(): array
    {
        return ['data' => 'array', 'next_action_at' => 'datetime'];
    }
}
