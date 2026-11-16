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

    /** Whether this insurer can quote right now (*_ENABLED=true in .env and not switched off in the admin panel). Set by forWebsite(). */
    public ?bool $quotingNow = null;

    /** Status chosen in the admin panel (live | coming_soon). */
    public function isLive(): bool
    {
        return $this->status === self::LIVE;
    }

    /**
     * What the website shows: "Live" only when the insurer is marked Live AND actually enabled.
     * A Live insurer that is not enabled yet (e.g. waiting for IP whitelisting) shows "Coming soon".
     */
    public function isLiveNow(): bool
    {
        return $this->isLive() && ($this->quotingNow ?? in_array($this->code, static::quotingCodes(), true));
    }

    /** @return list<string> insurer codes that can quote motor now (InsurerManager: .env + admin switch) */
    public static function quotingCodes(): array
    {
        return array_keys(rescue(fn () => app(\App\Insurers\InsurerManager::class)->forProduct('motor'), [], false));
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

    /**
     * Number of insurers the website may advertise for online motor quotes: marked Live in the table
     * AND actually quoting (.env + admin switch, via InsurerManager). Used for every insurer count on the site.
     */
    public static function liveQuotingCount(?Collection $insurers = null): int
    {
        return ($insurers ?? static::forWebsite())->filter->isLiveNow()->count();
    }

    /** "3 insurers compared" style label for service cards; no number when nothing is live. */
    public static function comparedLabel(?int $count = null): string
    {
        $count ??= static::liveQuotingCount();

        return match (true) {
            $count >= 2 => "{$count} insurers compared",
            $count === 1 => '1 insurer online',
            default => 'Advisor-assisted quotes',
        };
    }

    /** Insurers for the website strip, in admin order. Empty when the table is not reachable (e.g. before migrations). */
    public static function forWebsite(): Collection
    {
        $insurers = rescue(fn () => static::query()->ordered()->get(), collect(), false);
        $quoting = static::quotingCodes();

        return $insurers->each(fn (self $i) => $i->quotingNow = in_array($i->code, $quoting, true));
    }
}
