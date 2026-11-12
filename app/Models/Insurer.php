<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;

class Insurer extends Model
{
    /** Website display status (independent of `enabled`, which switches quoting on/off). */
    public const LIVE = 'live';

    public const COMING_SOON = 'coming_soon';

    public const STATUSES = [self::LIVE => 'Live', self::COMING_SOON => 'Coming soon'];

    protected $fillable = ['code', 'name', 'environment', 'enabled', 'status', 'sort', 'commission_rate', 'logo'];

    protected function casts(): array
    {
        return ['enabled' => 'boolean', 'sort' => 'integer', 'commission_rate' => 'decimal:2'];
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class)->withPivot('enabled');
    }

    public function scopeOrdered(Builder $q): Builder
    {
        return $q->orderBy('sort')->orderBy('name');
    }

    public function isLive(): bool
    {
        return $this->status === self::LIVE;
    }

    /** Public URL of the logo file, or null when there is none yet (the site then shows the name with a monogram). */
    public function logoUrl(): ?string
    {
        $path = $this->logo ?: "images/insurers/{$this->code}.png";

        return is_file(public_path($path)) ? asset($path) : null;
    }

    public function initials(): string
    {
        $words = preg_split('/\s+/', trim(preg_replace('/\b(Insurance|National|Takaful|Company)\b/i', '', $this->name))) ?: [];
        $words = array_values(array_filter($words));
        if (count($words) === 1) {
            return mb_strtoupper(mb_substr($words[0], 0, 3));
        }

        return mb_strtoupper(implode('', array_map(fn ($w) => mb_substr($w, 0, 1), array_slice($words, 0, 2))));
    }

    /** Insurers for the website strip, in admin order. Empty when the table is not reachable (e.g. before migrations). */
    public static function forWebsite(): Collection
    {
        return rescue(fn () => static::query()->ordered()->get(), collect(), false);
    }
}
