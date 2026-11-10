<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApiLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'insurer', 'operation', 'request_id', 'method', 'url', 'request', 'http_status', 'insurer_status',
        'response', 'duration_ms', 'error', 'subject_type', 'subject_id',
    ];

    protected function casts(): array
    {
        return ['request' => 'array', 'response' => 'array'];
    }
}
