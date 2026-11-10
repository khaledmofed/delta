<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Insurer reference data: nationality, emirate, plate codes, vehicle makes/models, ... */
class InsurerCode extends Model
{
    protected $fillable = ['insurer', 'type', 'code', 'label', 'parent', 'meta'];

    protected function casts(): array
    {
        return ['meta' => 'array'];
    }

    public static function options(string $insurer, string $type, ?string $parent = null): array
    {
        return static::query()
            ->where(compact('insurer', 'type'))
            ->when($parent !== null, fn ($q) => $q->where('parent', $parent))
            ->orderBy('label')
            ->pluck('label', 'code')
            ->all();
    }
}
