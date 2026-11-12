<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;

class Order extends Model
{
    /** Order state machine — mirrors the prototype. */
    public const STATES = [
        'QUOTE_SELECTED', 'CUSTOMER_DETAILS_PENDING', 'DOCUMENTS_PENDING', 'PAYMENT_PENDING', 'PAYMENT_SUCCESS',
        'POLICY_REQUESTED', 'INSURANCE_PROCESSING', 'POLICY_ISSUED',
        'PAYMENT_FAILED', 'DOCUMENTS_REJECTED', 'QUOTE_EXPIRED', 'INSURANCE_REJECTED', 'POLICY_FAILED', 'CANCELLED', 'REFUNDED',
    ];

    protected $fillable = [
        'reference', 'quote_request_id', 'quote_id', 'customer_id', 'insurer', 'state', 'selected_covers', 'premium',
        'vat', 'total', 'commission_rate', 'request_id', 'insurer_quote_no', 'payment_url', 'policy_start', 'history',
    ];

    /** Short status for the customer (header "My order" menu). */
    public function customerStatus(): string
    {
        return match ($this->state) {
            'POLICY_ISSUED' => 'Policy issued',
            'PAYMENT_PENDING' => 'Confirming payment',
            'PAYMENT_SUCCESS', 'POLICY_REQUESTED', 'INSURANCE_PROCESSING' => 'Policy being issued',
            'QUOTE_SELECTED', 'CUSTOMER_DETAILS_PENDING', 'DOCUMENTS_PENDING' => 'Payment pending',
            'PAYMENT_FAILED' => 'Payment not completed',
            default => 'Please contact us',
        };
    }

    protected function casts(): array
    {
        return ['selected_covers' => 'array', 'history' => 'array', 'policy_start' => 'date'];
    }

    protected static function booted(): void
    {
        static::creating(fn (self $m) => $m->reference ??= 'ORD-'.now()->format('Y').'-'.Str::upper(Str::random(8)));
    }

    public function getRouteKeyName(): string
    {
        return 'reference';
    }

    public function transition(string $state, ?string $note = null, string $actor = 'system'): void
    {
        if (! in_array($state, self::STATES, true)) {
            throw new \InvalidArgumentException("Unknown order state {$state}");
        }
        $history = $this->history ?? [];
        $history[] = ['state' => $state, 'at' => now()->toIso8601String(), 'note' => $note, 'actor' => $actor];
        $this->forceFill(['state' => $state, 'history' => $history])->save();
    }

    public function quoteRequest(): BelongsTo
    {
        return $this->belongsTo(QuoteRequest::class);
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function policy(): HasOne
    {
        return $this->hasOne(Policy::class);
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }
}
