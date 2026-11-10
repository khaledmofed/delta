@use('App\Support\AdminUi', 'UI')
<div class="tbl-wrap"><table class="tbl"><thead><tr><th>Service</th><th>Status</th><th class="num">Calls (24 h)</th><th class="num">Avg response</th><th class="num">Success</th><th>Last call</th></tr></thead><tbody>
@foreach ($health as $h)
<tr><td>{{ UI::who($h['code']) }}</td><td>{{ UI::pill($h['health'], match ($h['health']) { 'off' => $h['configured'] ? 'Disabled' : 'Not configured', 'no_traffic' => 'No calls yet', default => null }) }}</td><td class="num">{{ $h['calls'] }}</td><td class="num">{{ $h['avg_ms'] !== null ? number_format($h['avg_ms']).' ms' : '—' }}</td><td class="num">{{ $h['success_rate'] !== null ? $h['success_rate'].'%' : '—' }}</td><td>{{ UI::dt($h['last_call']) }}</td></tr>
@endforeach
</tbody></table></div>
