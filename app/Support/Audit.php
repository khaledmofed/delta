<?php

namespace App\Support;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

/** Writes sensitive back-office actions (logins, insurer switches, lead changes) to audit_logs. */
final class Audit
{
    public static function log(string $action, ?Model $subject = null, ?array $before = null, ?array $after = null, ?string $actor = null): void
    {
        $user = auth()->user();
        AuditLog::create([
            'actor' => $actor ?? ($user ? "{$user->email} (#{$user->id})" : 'system'),
            'action' => $action,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'before' => $before,
            'after' => $after,
            'ip' => request()->ip(),
        ]);
    }
}
