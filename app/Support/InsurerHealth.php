<?php

namespace App\Support;

use App\Insurers\InsurerManager;
use App\Models\ApiLog;
use App\Models\Insurer;

/** Per-insurer status for the admin panel, from config, the admin switch and the last 24 h of api_logs. */
final class InsurerHealth
{
    public static function all(): array
    {
        $manager = app(InsurerManager::class);
        $since = now()->subDay();
        $rows = Insurer::pluck('enabled', 'code');
        $out = [];
        foreach (config('insurers.providers', []) as $code => $cfg) {
            $logs = ApiLog::where('insurer', $code)->where('created_at', '>=', $since)->get(['http_status', 'error', 'duration_ms', 'created_at']);
            $ok = $logs->filter(fn ($l) => ! $l->error && $l->http_status >= 200 && $l->http_status < 300)->count();
            $calls = $logs->count();
            $configured = $manager->configured($code);
            $switchedOn = (bool) ($rows[$code] ?? true);
            $rate = $calls ? round($ok / $calls * 100, 1) : null;
            $out[$code] = [
                'code' => $code,
                'name' => $cfg['name'],
                'cfg' => $cfg,
                'configured' => $configured,
                'enabled' => $configured && $switchedOn,
                'switched_on' => $switchedOn,
                'calls' => $calls,
                'success_rate' => $rate,
                'avg_ms' => $calls ? (int) round($logs->avg('duration_ms')) : null,
                'last_call' => $logs->max('created_at'),
                'health' => ! ($configured && $switchedOn) ? 'off' : ($rate === null ? 'no_traffic' : ($rate >= 95 ? 'healthy' : ($rate >= 60 ? 'degraded' : 'down'))),
            ];
        }

        return $out;
    }
}
