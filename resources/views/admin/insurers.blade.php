@extends('layouts.admin')
@section('title', 'Providers')
@use('App\Support\AdminUi', 'UI')

@section('content')
<div class="sec-h"><div><h2>Insurance providers</h2><p>Each insurer is connected through its own adapter. Switching one off removes it from new quote requests.</p></div></div>
<div class="grid-d g-2">
@foreach ($insurers as $h)
  @php($cfg = $h['cfg'])
  <div class="card-d">
    <div class="hd"><div class="who">{{ UI::logo($h['code'], true) }}<div><h3 style="font-size:16px">{{ $h['name'] }}</h3><small>{{ parse_url($cfg['base_url'] ?? '', PHP_URL_HOST) }}</small></div></div>
      @can('providers.update')
      <form method="post" action="{{ route('admin.insurers.toggle', $h['code']) }}">@csrf<input type="hidden" name="enabled" value="0" /><label class="tgl" title="Enable / disable"><input type="checkbox" name="enabled" value="1" data-autosubmit @checked($h['enabled']) @disabled(! $h['configured']) /><i></i></label></form>
      @else
      <label class="tgl" title="{{ $h['enabled'] ? 'Enabled' : 'Disabled' }}"><input type="checkbox" @checked($h['enabled']) disabled /><i></i></label>
      @endcan
    </div>
    <div class="kvx">
      <div><small>Adapter</small><b><span class="mono">{{ class_basename($cfg['adapter']) }}</span></b></div>
      <div><small>Environment</small><b><span class="tagx">{{ str_contains($cfg['base_url'] ?? '', 'uat') ? 'UAT' : 'production' }}</span></b></div>
      <div><small>Products</small><b>{{ implode(', ', array_map('ucfirst', $cfg['products'] ?? [])) ?: '—' }}</b></div>
      <div><small>Timeout</small><b>{{ $cfg['timeout'] ?? 30 }} s</b></div>
      <div><small>Calls (24 h)</small><b>{{ $h['calls'] }}{{ $h['success_rate'] !== null ? ' · '.$h['success_rate'].'% OK' : '' }}</b></div>
      <div><small>Avg response</small><b>{{ $h['avg_ms'] !== null ? number_format($h['avg_ms']).' ms' : '—' }}</b></div>
      <div><small>Last call</small><b>{{ UI::dt($h['last_call']) }}</b></div>
      <div><small>Health</small><b>{{ UI::pill($h['health'], match ($h['health']) { 'off' => $h['configured'] ? 'Disabled' : 'Not configured', 'no_traffic' => 'No calls yet', default => null }) }}</b></div>
    </div>
    @if (! $h['configured'])<div class="note-d warn mt-2"><i class="fal fa-circle-info"></i><div>Not enabled in <span class="mono">.env</span> ({{ strtoupper($h['code']) }}_ENABLED / credentials). It cannot be switched on from here.</div></div>@endif
    <div class="row-flex mt-3">
      @can('providers.update')<form method="post" action="{{ route('admin.insurers.test', $h['code']) }}">@csrf<button class="btn-d sm" @disabled(! $h['configured'])><i class="far fa-plug"></i> Test connection</button></form>@endcan
      @can('api_logs.view')<a class="btn-d sm ghost" href="{{ route('admin.api-logs', ['insurer' => $h['code']]) }}">Logs</a>@endcan
    </div>
  </div>
@endforeach
</div>
<div class="note-d mt-3"><i class="fal fa-list-timeline"></i><div>Switching an insurer on or off and connection tests are written to the audit log. Credentials stay in <span class="mono">.env</span> and are never shown here.</div></div>
@endsection
