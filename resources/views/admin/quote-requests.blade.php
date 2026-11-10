@extends('layouts.admin')
@section('title', 'Quote Requests')
@use('App\Support\AdminUi', 'UI')
@use('App\Services\MotorQuotes')

@section('content')
<div class="sec-h"><div><h2>Quote requests</h2><p>Each request fans out to all enabled insurers. Faded logo = that insurer returned no offer.</p></div></div>
<form class="toolbar" method="get" action="{{ route('admin.quote-requests') }}">
  <select class="sel" name="status" data-autosubmit><option value="">All statuses</option>@foreach ($statuses as $s)<option value="{{ $s }}" @selected(request('status') === $s)>{{ UI::label($s) }}</option>@endforeach</select>
  <input class="inp" name="q" value="{{ request('q') }}" placeholder="Reference, customer name or email" style="width:280px" />
  <button class="btn-d sm" type="submit"><i class="far fa-magnifying-glass"></i> Search</button>
  @if (request()->hasAny(['status', 'q']))<a class="btn-d sm ghost" href="{{ route('admin.quote-requests') }}">Clear</a>@endif
  <span class="sp"></span><span class="muted">{{ $requests->total() }} requests</span>
</form>
@if ($requests->isEmpty())
<div class="card-d empty-d"><i class="fal fa-inbox"></i>No quote requests yet.</div>
@else
<div class="tbl-wrap"><table class="tbl"><thead><tr><th>Request</th><th>Customer</th><th>Vehicle</th><th>Insurers</th><th class="num">Offers</th><th>Status</th></tr></thead><tbody>
@foreach ($requests as $q)
@php($in = $q->input)
<tr class="click" data-href="{{ route('admin.quote-requests.show', [$q] + request()->query()) }}"><td><a href="{{ route('admin.quote-requests.show', [$q] + request()->query()) }}"><b>{{ $q->reference }}</b></a><span class="sub">{{ UI::dt($q->created_at) }}</span></td><td>{{ $in['name_en'] ?? '—' }}<span class="sub">{{ $in['email'] ?? '' }}</span></td><td>{{ trim(($in['year'] ?? '').' '.($in['make'] ?? '').' '.($in['model'] ?? '')) }}<span class="sub">{{ ($in['cover_type'] ?? '') === 'third_party' ? 'Third party' : 'Comprehensive' }}</span></td>
<td>@foreach ($q->insurer_results ?? [] as $code => $r)<span title="{{ UI::insurerName($code) }}: {{ $r['status'] ?? '' }}{{ ! empty($r['error_code']) ? ' ('.$r['error_code'].')' : '' }}" style="opacity:{{ ($r['status'] ?? '') === 'success' && ($r['offers'] ?? 0) > 0 ? 1 : .35 }}">{{ UI::logo($code) }}</span> @endforeach</td>
<td class="num">{{ $q->quotes_count }}</td><td>{{ UI::pill($q->status) }}</td></tr>
@endforeach
</tbody></table></div>
@include('admin.partials.pager', ['p' => $requests])
@endif
@endsection

@if ($open)
@php($q = $open)
@php($in = $q->input)
@section('drawer_close', route('admin.quote-requests', request()->query()))
@section('drawer')
<div class="hd"><h3>{{ $q->reference }}</h3>{{ UI::pill($q->status) }}<a class="btn-d sm ghost" style="margin-left:auto" href="{{ route('admin.quote-requests', request()->query()) }}"><i class="far fa-xmark"></i></a></div>
<div class="bd">
  <div class="card-d"><div class="kvx">
    <div><small>Customer</small><b>{{ $in['name_en'] ?? '—' }}<span class="sub muted">{{ $in['email'] ?? '' }} · {{ $in['mobile'] ?? '' }}</span></b></div>
    <div><small>Emirates ID</small><b>{{ UI::maskEid($in['emirates_id'] ?? null) }}<span class="sub muted">expires {{ isset($in['emirates_id_expiry']) ? \Illuminate\Support\Carbon::parse($in['emirates_id_expiry'])->format('d M Y') : '—' }}</span></b></div>
    <div><small>Vehicle</small><b>{{ trim(($in['year'] ?? '').' '.($in['make'] ?? '').' '.($in['model'] ?? '')) }}<span class="sub muted">{{ $in['chassis'] ?? '' }}</span></b></div>
    <div><small>Plate</small><b>{{ trim(MotorQuotes::label('plateSource', $in['plate_source'] ?? null).' '.MotorQuotes::label('plateCode', $in['plate_code'] ?? null).' '.($in['plate_number'] ?? '')) ?: '—' }}</b></div>
    <div><small>Cover</small><b>{{ ($in['cover_type'] ?? '') === 'third_party' ? 'Third party' : 'Comprehensive' }}{{ ! empty($in['repair']) ? ' · '.ucfirst($in['repair']) : '' }}</b></div>
    <div><small>Add-ons asked</small><b>{{ ! empty($in['addons']) ? implode(', ', $in['addons']) : '—' }}</b></div>
    <div><small>Policy start</small><b>{{ isset($in['policy_start']) ? \Illuminate\Support\Carbon::parse($in['policy_start'])->format('d M Y') : '—' }}</b></div>
    <div><small>Created</small><b>{{ UI::dt($q->created_at) }}</b></div>
  </div></div>

  <div class="card-d"><div class="hd"><h3>Insurer responses</h3><small>Customer message shown on the results page</small></div>
    @forelse ($q->insurer_results ?? [] as $code => $r)
    <div style="padding:10px 0;border-bottom:1px solid var(--line)">
      <div class="row-flex" style="justify-content:space-between">{{ UI::who($code) }}<span class="muted">{{ isset($r['ms']) ? number_format($r['ms'] / 1000, 2).'s' : '' }}</span>{{ UI::pill($r['status'] ?? 'pending', match ($r['status'] ?? 'pending') { 'success' => ($r['offers'] ?? 0).' offers', 'pending' => 'Pending', default => 'Error'.(! empty($r['error_code']) ? ' '.$r['error_code'] : '') }) }}</div>
      @if (($r['status'] ?? '') !== 'success' && ($r['status'] ?? '') !== 'pending')
        <div class="sub mt-1"><b style="font-weight:600;color:var(--ink-2)">Customer saw:</b> {{ $r['message'] ?? '—' }}</div>
        @if (! empty($r['error']))<div class="sub" style="color:var(--bad)"><b style="font-weight:600">Technical:</b> {{ $r['error'] }}</div>@endif
      @endif
      @if (! empty($r['request_id']))<div class="sub">Request ID @can('api_logs.view')<a class="mono" href="{{ route('admin.api-logs', ['insurer' => $code, 'request_id' => $r['request_id']]) }}">{{ $r['request_id'] }}</a>@else<span class="mono">{{ $r['request_id'] }}</span>@endcan</div>@endif
    </div>
    @empty<p class="muted">No insurer was asked.</p>@endforelse
  </div>

  <div class="card-d"><div class="hd"><h3>Normalised quotes</h3><small>{{ $q->quotes->count() }}</small></div>
    @if ($q->quotes->isEmpty())<p class="muted">No offers.</p>@else
    <div class="tbl-wrap"><table class="tbl"><thead><tr><th>Insurer</th><th>Plan</th><th>Type</th><th class="num">Premium</th><th class="num">Total</th><th>Valid until</th></tr></thead><tbody>
      @foreach ($q->quotes->sortBy('total') as $x)<tr><td>{{ UI::logo($x->insurer) }}</td><td>{{ $x->plan_name }}<span class="sub">{{ $x->plan_code }}{{ ($x->raw['indicative'] ?? false) ? ' · indicative' : '' }}</span></td><td>{{ $x->cover_type === 'comprehensive' ? 'Comp.' : 'TPL' }}{{ $x->repair_type ? ' · '.$x->repair_type : '' }}</td><td class="num">{{ UI::money($x->premium) }}</td><td class="num"><b>{{ UI::money($x->total) }}</b></td><td>{{ $x->valid_until?->isPast() ? UI::pill('expired') : $x->valid_until?->format('d M Y') }}</td></tr>@endforeach
    </tbody></table></div>@endif
  </div>

  <div class="card-d"><div class="hd"><h3>Documents</h3><small>Private disk</small></div>
    @forelse ($q->documents as $d)<div class="row-flex" style="justify-content:space-between;padding:6px 0;border-bottom:1px solid var(--line)"><span style="font-size:13px">{{ MotorQuotes::DOCUMENTS[$d->type]['label'] ?? $d->type }}<span class="sub muted">{{ $d->original_name }} · {{ number_format($d->size / 1024) }} KB · DIN {{ $d->insurer_code ?? '—' }}</span></span>{{ UI::pill($d->status) }}</div>@empty<p class="muted">None uploaded.</p>@endforelse
  </div>
</div>
@endsection
@endif
