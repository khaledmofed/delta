{{-- Key/value table. $rows = [label => value]; empty values are skipped. --}}
<table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="font-size:14px;line-height:1.6;border-collapse:collapse;margin:0 0 20px">
@foreach ($rows as $k => $v)
@continue($v === null || $v === '')
  <tr><td style="padding:7px 0;border-bottom:1px solid #e3e9ef;color:#7a8a99;width:160px;vertical-align:top">{{ $k }}</td><td style="padding:7px 0;border-bottom:1px solid #e3e9ef;font-weight:bold">{{ $v }}</td></tr>
@endforeach
</table>
