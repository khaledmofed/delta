@extends('emails.layout')
@section('title', 'We received your request')
@php($d = $lead->data ?? [])
@section('body')
<h1 style="margin:0 0 12px;font-size:22px;color:#122a43">Thank you, {{ \Illuminate\Support\Str::before($lead->name, ' ') }}.</h1>
<p style="margin:0 0 16px">We have received your request for <b>{{ $lead->productName() }}</b>. A Delta advisor will contact you{{ ! empty($d['contact_pref']) ? ' by '.strtolower($d['contact_pref']) : '' }} to discuss your options.</p>
<table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="background:#f2f8fc;border-radius:10px;margin:0 0 18px">
  <tr><td style="padding:14px 18px;font-size:14px;line-height:1.7">
    <b style="color:#256383">Your reference:</b> {{ $lead->reference }}<br />
    <b style="color:#256383">Mobile:</b> +{{ $lead->mobile }}<br />
    @foreach ($lead->supportDetails() as $k => $v)<b style="color:#256383">{{ $k }}:</b> {{ $v }}<br />@endforeach
    @if (! empty($d['emirate']))<b style="color:#256383">Emirate:</b> {{ $d['emirate'] }}<br />@endif
    @if (! empty($d['message']))<b style="color:#256383">Your message:</b><br /><span style="white-space:pre-line">{{ $d['message'] }}</span>@endif
  </td></tr>
</table>
<p style="margin:0 0 6px">Please quote your reference if you contact us. You can reach us on {{ config('site.phone') }} or reply to this email.</p>
<p style="margin:16px 0 0;color:#7a8a99;font-size:13px">You received this email because you sent a request on our website. If this wasn’t you, please let us know.</p>
@endsection
