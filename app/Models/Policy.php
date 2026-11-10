<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Policy extends Model
{
    protected $fillable = ['order_id', 'insurer', 'policy_no', 'status', 'starts_on', 'ends_on', 'sum_insured', 'total', 'documents', 'raw'];

    protected function casts(): array
    {
        return ['documents' => 'array', 'raw' => 'array', 'starts_on' => 'date', 'ends_on' => 'date'];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
