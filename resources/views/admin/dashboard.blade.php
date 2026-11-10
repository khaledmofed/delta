@extends('layouts.admin')
@section('title', 'Dashboard')
@use('App\Support\AdminUi', 'UI')

@php
    $mx = max(1, $days->max('n'));
    $maxPrem = max(1, (float) $premiumByInsurer->max());
@endphp

@section('content')
<div class="sec-h"><div><h2>Operations overview</h2><p>Phase 1 · Motor Insurance digital platform</p></div><div class="row-flex"><span class="muted">Updated {{ now()->format('H:i') }}</span></div></div>
<div class="grid-d g-4">
  <div class="card-d kpi"><i class="fal fa-sack-dollar ic"></i><small>Gross written premium</small><b>{{ 'AED '.number_format(round($kpi['premium'])) }}</b><span>{{ $kpi['policies'] }} policies · excl. VAT</span></div>
  <div class="card-d kpi"><i class="fal fa-file-magnifying-glass ic"></i><small>Quote requests</small><b>{{ $kpi['quoteRequests'] }}</b><span>Motor · all time</span></div>
  <div class="card-d kpi"><i class="fal fa-arrow-trend-up ic"></i><small>Quote → policy</small><b>{{ $kpi['conversion'] }}%</b><span>{{ $kpi['policies'] }} of {{ $kpi['quoteRequests'] }} requests</span></div>
  <div class="card-d kpi"><i class="fal fa-triangle-exclamation ic"></i><small>Needs attention</small><b>{{ $kpi['attention'] }}</b><span>Payments · pending policies · leads</span></div>
</div>

<div class="grid-d g-21 mt-3">
  <div class="card-d"><div class="hd"><h3>Quote requests · last 14 days</h3><small>Motor</small></div>
    <div class="cols">@foreach ($days as $x)<div class="c"><span class="tt">{{ $x['d']->format('d M Y') }}: {{ $x['n'] }}</span><div class="b" style="height:{{ $x['n'] / $mx * 100 }}%"></div></div>@endforeach</div>
    <div class="cols-x">@foreach ($days as $i => $x)<span>{{ $i % 2 ? '' : $x['d']->day }}</span>@endforeach</div>
  </div>
  <div class="card-d"><div class="hd"><h3>Needs attention</h3></div>
    @forelse ($attention as [$tone, $text, $href])<a href="{{ $href }}" class="row-flex" style="padding:9px 0;border-bottom:1px solid var(--line);color:var(--ink)"><span class="pill {{ $tone }}" style="padding:3px 6px"></span><span style="flex:1;font-size:13.5px">{{ $text }}</span><i class="far fa-angle-right muted"></i></a>@empty<p class="muted">All clear.</p>@endforelse
  </div>
</div>

<div class="grid-d g-2 mt-3">
  <div class="card-d"><div class="hd"><h3>Premium by insurer</h3><small>AED, excl. VAT · issued policies</small></div>
    @if ($premiumByInsurer->isEmpty())<p class="muted">No policies issued yet.</p>@else<div class="bars">@foreach ($premiumByInsurer as $code => $sum)<div class="bar-row" title="{{ UI::insurerName($code) }}: {{ number_format($sum) }}"><span>{{ UI::insurerName($code) }}</span><div class="track"><div class="fill" style="width:{{ number_format($sum / $maxPrem * 100, 1, '.', '') }}%"></div></div><span class="v">{{ 'AED '.number_format(round($sum)) }}</span></div>@endforeach</div>@endif
  </div>
  <div class="card-d"><div class="hd"><h3>Conversion funnel</h3><small>Motor</small></div>
    <div class="fun">@foreach ($funnel as [$label, $n])<div class="st"><span>{{ $label }}</span><div class="t"><i style="width:{{ $funnel[0][1] ? $n / $funnel[0][1] * 100 : 0 }}%"></i></div><b style="text-align:right">{{ $n }}</b></div>@endforeach</div>
  </div>
</div>

<div class="card-d mt-3"><div class="hd"><h3>Integration health</h3><small>Last 24 h · api_logs</small>@can('api_logs.view')<a href="{{ route('admin.api-logs') }}">Details</a>@endcan</div>
  @include('admin.partials.health', ['health' => $health])
</div>
@endsection
