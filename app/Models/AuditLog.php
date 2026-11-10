<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['actor', 'action', 'subject_type', 'subject_id', 'before', 'after', 'ip'];

    protected function casts(): array
    {
        return ['before' => 'array', 'after' => 'array'];
    }
}
