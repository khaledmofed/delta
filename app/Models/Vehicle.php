<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Vehicle extends Model
{
    protected $fillable = [
        'customer_id', 'chassis', 'make', 'model', 'trim', 'year', 'value', 'tcf_number', 'plate_source',
        'plate_code', 'plate_number', 'registration_date', 'is_new', 'is_gcc', 'finance_bank',
    ];

    protected function casts(): array
    {
        return ['registration_date' => 'date', 'is_new' => 'boolean', 'is_gcc' => 'boolean', 'value' => 'decimal:2'];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
