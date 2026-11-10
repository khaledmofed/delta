<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/** Consultation / quote request from the website (product pages, contact page). data: message, contact_pref, emirate, source, page. */
class Lead extends Model
{
    protected $fillable = ['reference', 'product', 'name', 'email', 'mobile', 'data', 'status', 'owner_id', 'next_action_at'];

    protected function casts(): array
    {
        return ['data' => 'array', 'next_action_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::creating(fn (self $m) => $m->reference ??= 'LD-'.now()->format('ymd').'-'.Str::upper(Str::random(5)));
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function productName(): string
    {
        return match ($this->product) {
            'general' => 'General enquiry',
            'claims' => 'Claims & Support',
            default => config("site.services.{$this->product}.name", ucfirst($this->product)),
        };
    }

    /** Claims & Support details as label => value (request type, policy, insurer), empty values skipped. */
    public function supportDetails(): array
    {
        $d = $this->data ?? [];

        return array_filter([
            'Request type' => isset($d['request_type']) ? config("site.claims.request_types.{$d['request_type']}", $d['request_type']) : null,
            'Policy number' => $d['policy_no'] ?? null,
            'Insurer' => isset($d['insurer']) ? ($d['insurer'] === 'other' ? 'Other insurer' : config("insurers.providers.{$d['insurer']}.name", $d['insurer'])) : null,
        ]);
    }
}
