<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Quote extends Model
{
    protected $fillable = [
        'quote_request_id', 'insurer', 'plan_code', 'plan_name', 'cover_type', 'repair_type', 'sum_insured',
        'premium', 'vat', 'total', 'deductible', 'covers', 'insurer_ref', 'raw', 'valid_until',
    ];

    protected function casts(): array
    {
        return [
            'covers' => 'array', 'raw' => 'array', 'valid_until' => 'datetime',
            'premium' => 'decimal:2', 'vat' => 'decimal:2', 'total' => 'decimal:2',
            'sum_insured' => 'decimal:2', 'deductible' => 'decimal:2',
        ];
    }

    public function quoteRequest(): BelongsTo
    {
        return $this->belongsTo(QuoteRequest::class);
    }
}
