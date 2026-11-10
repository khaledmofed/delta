<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Insurer extends Model
{
    protected $fillable = ['code', 'name', 'environment', 'enabled', 'commission_rate', 'logo'];

    protected function casts(): array
    {
        return ['enabled' => 'boolean', 'commission_rate' => 'decimal:2'];
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class)->withPivot('enabled');
    }
}
