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
        return $this->product === 'general' ? 'General enquiry' : config("site.services.{$this->product}.name", ucfirst($this->product));
    }
}
