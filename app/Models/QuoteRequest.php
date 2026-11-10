<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;

class QuoteRequest extends Model
{
    protected $fillable = ['reference', 'product', 'customer_id', 'vehicle_id', 'input', 'status', 'insurer_results'];

    protected function casts(): array
    {
        return ['input' => 'encrypted:array', 'insurer_results' => 'array'];
    }

    protected static function booted(): void
    {
        static::creating(fn (self $m) => $m->reference ??= 'QR-'.now()->format('ymd').'-'.Str::upper(Str::random(6)));
    }

    public function quotes(): HasMany
    {
        return $this->hasMany(Quote::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }
}
