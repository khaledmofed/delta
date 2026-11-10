<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Product extends Model
{
    protected $fillable = ['code', 'name', 'status', 'capabilities', 'sort'];

    protected function casts(): array
    {
        return ['capabilities' => 'array'];
    }

    public function insurers(): BelongsToMany
    {
        return $this->belongsToMany(Insurer::class)->withPivot('enabled');
    }

    public function can(string $capability): bool
    {
        return $this->status === 'active' && in_array($capability, $this->capabilities ?? [], true);
    }
}
