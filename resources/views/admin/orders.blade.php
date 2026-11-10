@extends('layouts.admin')
@section('title', 'Orders')
@use('App\Support\AdminUi', 'UI')

@section('content')
<div class="sec-h"><div><h2>Orders</h2><p>Order ≠ Quote ≠ Policy. Click an order to see its state machine.</p></div></div>
<form class="toolbar" method="get" action="{{ route('admin.orders') }}">
  <select class="sel" name="state" data-autosubmit><option value="">All states</option>@foreach ($states as $s)<option value="{{ $s }}" @selected(request('state') === $s)>{{ UI::label($s) }}</option>@endforeach</select>
  <input class="inp" name="q" value="{{ request('q') }}" placeholder="Order ref, customer name or email" style="width:280px" />
  <button class="btn-d sm" type="submit"><i class="far fa-magnifying-glass"></i> Search</button>
  @if (request()->hasAny(['state', 'q']))<a class="btn-d sm ghost" href="{{ route('admin.orders') }}">Clear</a>@endif
  <span class="sp"></span><span class="muted">{{ $orders->total() }} orders</span>
</form>
@if ($orders->isEmpty())
<div class="card-d empty-d"><i class="fal fa-inbox"></i>No orders yet.</div>
@else
<div class="tbl-wrap"><table class="tbl"><thead><tr><th>Order</th><th>Customer</th><th>Insurer</th><th>State</th><th>Updated</th><th class="num">Total</th></tr></thead><tbody>
@foreach ($orders as $o)
<tr class="click" data-href="{{ route('admin.orders.show', [$o] + request()->query()) }}"><td><a href="{{ route('admin.orders.show', [$o] + request()->query()) }}"><b>{{ $o->reference }}</b></a><span class="sub">{{ UI::dt($o->created_at) }}</span></td><td>{{ $o->customer->name ?? '—' }}<span class="sub">{{ $o->quote->plan_name ?? '' }}</span></td><td>{{ UI::who($o->insurer) }}</td><td>{{ UI::pill($o->state) }}@if ($o->policy)<span class="sub">{{ $o->policy->policy_no }}</span>@endif</td><td>{{ UI::dt($o->updated_at) }}</td><td class="num">{{ UI::money($o->total) }}</td></tr>
@endforeach
</tbody></table></div>
@include('admin.partials.pager', ['p' => $orders])
@endif
@endsection

@if ($open)
@php
    $o = $open;
    $hist = collect($o->history ?? []);
    $reached = $hist->groupBy('state')->map->last();
    $path = ['QUOTE_SELECTED', 'PAYMENT_PENDING', 'PAYMENT_SUCCESS', 'INSURANCE_PROCESSING', 'POLICY_ISSUED'];
    $alt = array_values(array_filter(['PAYMENT_FAILED', 'DOCUMENTS_REJECTED', 'QUOTE_EXPIRED', 'INSURANCE_REJECTED', 'POLICY_FAILED', 'CANCELLED', 'REFUNDED'], fn ($s) => $reached->has($s)));
    $in = $o->quoteRequest?->input ?? [];
@endphp
@section('drawer_close', route('admin.orders', request()->query()))
@section('drawer')
<div class="hd"><h3>{{ $o->reference }}</h3>{{ UI::pill($o->state) }}<a class="btn-d sm ghost" style="margin-left:auto" href="{{ route('admin.orders', request()->query()) }}"><i class="far fa-xmark"></i></a></div>
<div class="bd">
  <div class="card-d"><div class="kvx">
    <div><small>Customer</small><b>{{ $o->customer->name ?? '—' }}<span class="sub muted">{{ $o->customer->email ?? '' }} · {{ $o->customer->mobile ?? '' }}</span></b></div>
    <div><small>Vehicle</small><b>{{ trim(($in['year'] ?? '').' '.($in['make'] ?? '').' '.($in['model'] ?? '')) ?: '—' }}<span class="sub muted">{{ $in['chassis'] ?? '' }}</span></b></div>
    <div><small>Insurer / plan</small><b>{{ UI::insurerName($o->insurer) }}{{ $o->quote ? ' — '.$o->quote->plan_name : '' }}</b></div>
    <div><small>Quote request</small><b>@if ($o->quoteRequest && auth()->user()->can('quotes.view'))<a href="{{ route('admin.quote-requests.show', $o->quoteRequest) }}">{{ $o->quoteRequest->reference }}</a>@else{{ $o->quoteRequest->reference ?? '—' }}@endif</b></div>
    <div><small>Premium</small><b>{{ UI::money($o->premium) }}</b></div>
    <div><small>VAT</small><b>{{ UI::money($o->vat) }}</b></div>
    <div><small>Total</small><b>{{ UI::money($o->total) }}</b></div>
    <div><small>Policy start</small><b>{{ $o->policy_start?->format('d M Y') ?? '—' }}</b></div>
    <div><small>Insurer request ID</small><b>@if ($o->request_id)@can('api_logs.view')<a class="mono" href="{{ route('admin.api-logs', ['request_id' => $o->request_id]) }}">{{ $o->request_id }}</a>@else<span class="mono">{{ $o->request_id }}</span>@endcan @else — @endif</b></div>
    <div><small>Insurer quote no.</small><b>{{ $o->insurer_quote_no ?? '—' }}</b></div>
    <div><small>Policy</small><b>{{ $o->policy?->policy_no ?? '—' }}@if ($o->policy)<span class="sub muted">{{ $o->policy->starts_on?->format('d M Y') }} → {{ $o->policy->ends_on?->format('d M Y') }}</span>@endif</b></div>
    <div><small>Optional covers</small><b>{{ $o->selected_covers ? implode(', ', $o->selected_covers) : '—' }}</b></div>
  </div></div>
  <div class="grid-d g-2">
    <div class="card-d"><div class="hd"><h3>State machine</h3></div><ul class="sm">
      @foreach ($path as $s)@php($h = $reached[$s] ?? null)<li class="{{ $h ? 'done' : 'todo' }}{{ $o->state === $s ? ' cur' : '' }}"><span class="d">@if ($h)<i class="fas fa-check"></i>@endif</span><b>{{ UI::label($s) }}</b><small>{{ $h ? UI::dt($h['at']).' · '.($h['actor'] ?? '') : 'Pending' }}</small></li>@endforeach
      @foreach ($alt as $s)@php($h = $reached[$s])<li class="bad"><span class="d"><i class="fas fa-xmark"></i></span><b>{{ UI::label($s) }}</b><small>{{ UI::dt($h['at']) }}{{ ! empty($h['note']) ? ' — '.$h['note'] : '' }}</small></li>@endforeach
    </ul></div>
    <div class="grid-d" style="align-content:start">
      <div class="card-d"><div class="hd"><h3>Payment attempts</h3><small>Insurer-hosted</small></div>
        @forelse ($o->payments->sortBy('attempt') as $p)<div class="row-flex" style="justify-content:space-between;padding:6px 0;border-bottom:1px solid var(--line)"><span>#{{ $p->attempt }} · {{ UI::money($p->amount) }}<span class="sub muted">{{ UI::dt($p->created_at) }}{{ $p->reference ? ' · '.$p->reference : '' }}</span></span>{{ UI::pill($p->status) }}</div>@empty<p class="muted">No payment yet.</p>@endforelse
      </div>
      <div class="card-d"><div class="hd"><h3>History</h3><small>{{ $hist->count() }} events</small></div><ul class="sm">
        @foreach ($hist->reverse() as $h)<li class="done"><span class="d"><i class="fas fa-check"></i></span><b>{{ UI::label($h['state']) }}</b><small>{{ UI::dt($h['at']) }} · {{ $h['actor'] ?? '' }}{{ ! empty($h['note']) ? ' — '.$h['note'] : '' }}</small></li>@endforeach
      </ul></div>
    </div>
  </div>
</div>
@endsection
@endif
