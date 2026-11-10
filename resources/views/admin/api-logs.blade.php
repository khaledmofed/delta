@extends('layouts.admin')
@section('title', 'API Logs')
@use('App\Support\AdminUi', 'UI')
@use('App\Http\Controllers\Admin\ApiLogController')

@section('content')
<div class="sec-h"><div><h2>Insurer API calls</h2><p>Every request to an insurer (api_logs). Passwords, API keys, tokens and document content are redacted before saving.</p></div></div>
<form class="toolbar" method="get" action="{{ route('admin.api-logs') }}">
  <select class="sel" name="insurer" data-autosubmit><option value="">All insurers</option>@foreach ($insurers as $c)<option value="{{ $c }}" @selected(request('insurer') === $c)>{{ UI::insurerName($c) }}</option>@endforeach</select>
  <select class="sel" name="operation" data-autosubmit><option value="">All operations</option>@foreach ($operations as $op)<option value="{{ $op }}" @selected(request('operation') === $op)>{{ $op }}</option>@endforeach</select>
  <select class="sel" name="result" data-autosubmit><option value="">Any result</option><option value="ok" @selected(request('result') === 'ok')>Success (2xx)</option><option value="failed" @selected(request('result') === 'failed')>Failed</option></select>
  <input class="inp" name="request_id" value="{{ request('request_id') }}" placeholder="Request ID (X-REQUEST-ID)" style="width:300px" />
  <button class="btn-d sm" type="submit"><i class="far fa-magnifying-glass"></i> Filter</button>
  @if (request()->hasAny(['insurer', 'operation', 'result', 'request_id']))<a class="btn-d sm ghost" href="{{ route('admin.api-logs') }}">Clear</a>@endif
  <span class="sp"></span><span class="muted">{{ $logs->total() }} calls</span>
</form>
@if ($logs->isEmpty())
<div class="card-d empty-d"><i class="fal fa-inbox"></i>No API calls match.</div>
@else
<div class="tbl-wrap"><table class="tbl"><thead><tr><th>Time</th><th>Insurer</th><th>Operation</th><th class="num">Latency</th><th>Result</th><th>Request ID</th></tr></thead><tbody>
@foreach ($logs as $l)
@php($bad = ApiLogController::failed($l))
<tr class="click" data-href="{{ route('admin.api-logs.show', [$l] + request()->query()) }}"><td>{{ UI::dt($l->created_at) }}</td><td>{{ UI::who($l->insurer) }}</td><td><span class="mono">{{ $l->method }} {{ $l->operation }}()</span></td><td class="num">{{ $l->duration_ms !== null ? number_format($l->duration_ms).' ms' : '—' }}</td>
<td>{{ UI::pill($bad ? 'failed' : 'active', ($l->http_status ?: 'no response').($l->insurer_status ? ' · '.$l->insurer_status : '')) }}@if ($l->error)<span class="sub" style="color:var(--bad);max-width:300px">{{ \Illuminate\Support\Str::limit($l->error, 90) }}</span>@endif</td>
<td>@if ($l->request_id)<a class="mono" href="{{ route('admin.api-logs', ['request_id' => $l->request_id]) }}">{{ $l->request_id }}</a>@else<span class="muted">—</span>@endif</td></tr>
@endforeach
</tbody></table></div>
@include('admin.partials.pager', ['p' => $logs])
@endif
@endsection

@if ($open)
@php($l = $open)
@section('drawer_close', route('admin.api-logs', request()->query()))
@section('drawer')
<div class="hd"><h3>{{ $l->operation }}()</h3>{{ UI::pill(ApiLogController::failed($l) ? 'failed' : 'active', (string) ($l->http_status ?: 'no response')) }}<a class="btn-d sm ghost" style="margin-left:auto" href="{{ route('admin.api-logs', request()->query()) }}"><i class="far fa-xmark"></i></a></div>
<div class="bd">
  <div class="card-d"><div class="kvx">
    <div><small>Insurer</small><b>{{ UI::insurerName($l->insurer) }}</b></div>
    <div><small>Time</small><b>{{ UI::dt($l->created_at) }}</b></div>
    <div style="grid-column:1/-1"><small>Endpoint</small><b class="mono" style="word-break:break-all">{{ $l->method }} {{ $l->url }}</b></div>
    <div><small>HTTP status</small><b>{{ $l->http_status ?? '—' }}</b></div>
    <div><small>Insurer status</small><b>{{ $l->insurer_status ?? '—' }}</b></div>
    <div><small>Latency</small><b>{{ $l->duration_ms !== null ? number_format($l->duration_ms).' ms' : '—' }}</b></div>
    <div><small>Request ID</small><b class="mono">{{ $l->request_id ?? '—' }}</b></div>
    @if ($l->error)<div style="grid-column:1/-1"><small>Error</small><b style="color:var(--bad)">{{ $l->error }}</b></div>@endif
  </div></div>
  <div class="note-d"><i class="fal fa-shield-check"></i><div>Shown as stored: secrets are replaced by <span class="mono">***</span> and document content by a size marker when the call is logged.</div></div>
  <div class="card-d"><div class="hd"><h3>Request</h3></div><pre style="background:#0f1f30;color:#d7e9f4;padding:14px;border-radius:10px;font-size:12px;overflow:auto;max-height:420px;margin:0">{{ UI::json($l->request) }}</pre></div>
  <div class="card-d"><div class="hd"><h3>Response</h3></div><pre style="background:#0f1f30;color:#d7e9f4;padding:14px;border-radius:10px;font-size:12px;overflow:auto;max-height:520px;margin:0">{{ UI::json($l->response) }}</pre></div>
</div>
@endsection
@endif
