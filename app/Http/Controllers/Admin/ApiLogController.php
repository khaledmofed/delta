<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApiLog;
use Illuminate\Http\Request;

/** Viewer for api_logs (already redacted when written: no passwords, keys, tokens or document content). */
class ApiLogController extends Controller
{
    public function index(Request $request, ?ApiLog $log = null)
    {
        $q = ApiLog::latest('id');
        if ($insurer = $request->query('insurer')) {
            $q->where('insurer', $insurer);
        }
        if ($rid = trim((string) $request->query('request_id'))) {
            // DIN agency variants use "<uuid>-A", so a prefix search finds both calls.
            $q->where('request_id', 'like', addcslashes($rid, '%_\\').'%');
        }
        if ($op = $request->query('operation')) {
            $q->where('operation', $op);
        }
        if ($request->query('result') === 'failed') {
            $q->where(fn ($w) => $w->whereNotNull('error')->orWhereNull('http_status')->orWhere('http_status', '>=', 400));
        } elseif ($request->query('result') === 'ok') {
            $q->whereNull('error')->whereBetween('http_status', [200, 299]);
        }

        return view('admin.api-logs', [
            'logs' => $q->paginate(50)->withQueryString(),
            'insurers' => array_keys(config('insurers.providers', [])),
            'operations' => ApiLog::distinct()->orderBy('operation')->pluck('operation'),
            'open' => $log,
        ]);
    }

    public function show(Request $request, ApiLog $log)
    {
        return $this->index($request, $log);
    }

    public static function failed(ApiLog $l): bool
    {
        return $l->error || ! $l->http_status || $l->http_status >= 400;
    }
}
