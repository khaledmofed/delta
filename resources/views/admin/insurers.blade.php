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
<div class="sec-h mt-4"><div><h2>Website logo strip</h2><p>“Trusted by leading UAE insurers” on the home and motor pages. <b>Live</b> shows the insurer in full colour with a Live tag; <b>Coming soon</b> shows it faded. Lower position = shown first. This does not switch quoting on or off.</p></div></div>
<div class="tbl-wrap"><table class="tbl"><thead><tr><th>Insurer</th><th>Website status</th><th class="num">Position</th><th>Online connection</th>@can('providers.update')<th></th>@endcan</tr></thead><tbody>
@foreach ($website as $w)
  <tr>
    <td><div class="who">{{ UI::logo($w->code) }}<b>{{ $w->name }}</b></div></td>
    @can('providers.update')
    <td><select class="sel" name="status" form="ins-display-{{ $w->code }}" aria-label="Website status for {{ $w->name }}">@foreach (\App\Models\Insurer::STATUSES as $k => $v)<option value="{{ $k }}" @selected($w->status === $k)>{{ $v }}</option>@endforeach</select></td>
    <td class="num"><input class="inp" type="number" name="sort" min="0" max="999" value="{{ $w->sort }}" form="ins-display-{{ $w->code }}" style="width:80px" aria-label="Position of {{ $w->name }}" /></td>
    @else
    <td>{{ \App\Models\Insurer::STATUSES[$w->status] ?? $w->status }}</td><td class="num">{{ $w->sort }}</td>
    @endcan
    <td>{{ isset($insurers[$w->code]) ? ($insurers[$w->code]['enabled'] ? 'Connected · quoting' : 'Connected · switched off') : 'Not connected yet' }}</td>
    @can('providers.update')<td><form id="ins-display-{{ $w->code }}" method="post" action="{{ route('admin.insurers.display', $w->code) }}">@csrf<button class="btn-d sm">Save</button></form></td>@endcan
  </tr>
@endforeach
</tbody></table></div>
<div class="note-d mt-3"><i class="fal fa-list-timeline"></i><div>Switching an insurer on or off and connection tests are written to the audit log. Credentials stay in <span class="mono">.env</span> and are never shown here.</div></div>
@endsection
