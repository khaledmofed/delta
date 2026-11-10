@extends('emails.layout')
@section('title', 'New lead')
@php($d = $lead->data ?? [])
@section('body')
<h1 style="margin:0 0 12px;font-size:20px;color:#122a43">New lead — {{ $lead->productName() }}</h1>
<table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="font-size:14px;line-height:1.6;border-collapse:collapse">
  @foreach ([
      'Reference' => $lead->reference,
      'Name' => $lead->name,
      'Mobile' => '+'.$lead->mobile,
      'Email' => $lead->email,
      'Preferred contact' => $d['contact_pref'] ?? '—',
      'Emirate' => $d['emirate'] ?? '—',
      'Source' => ($d['source'] ?? '') === 'contact' ? 'Contact page' : 'Product page'.(! empty($d['page']) ? ' ('.$d['page'].')' : ''),
      'Received' => $lead->created_at?->timezone(config('app.timezone'))->format('d M Y, H:i'),
  ] as $k => $v)
  <tr><td style="padding:7px 0;border-bottom:1px solid #e3e9ef;color:#7a8a99;width:150px;vertical-align:top">{{ $k }}</td><td style="padding:7px 0;border-bottom:1px solid #e3e9ef;font-weight:bold">{{ $v }}</td></tr>
  @endforeach
</table>
@if (! empty($d['message']))
<p style="margin:18px 0 6px;color:#7a8a99;font-size:13px">Message</p>
<div style="background:#f2f8fc;border-radius:10px;padding:14px 18px;white-space:pre-line">{{ $d['message'] }}</div>
@endif
<p style="margin:22px 0 0"><a href="{{ route('admin.leads', ['status' => 'new']) }}" style="display:inline-block;background:#122a43;color:#ffffff;text-decoration:none;font-weight:bold;padding:11px 18px;border-radius:8px">Open in the admin panel</a></p>
<p style="margin:14px 0 0;color:#7a8a99;font-size:13px">Reply to this email to answer the customer directly.</p>
@endsection
