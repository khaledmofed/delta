@extends('emails.layout')
@section('title', $what)
@section('body')
<h1 style="margin:0 0 12px;font-size:20px;color:#c24141">{{ $what }}</h1>
@if ($detail)<div style="background:#fbeaea;border-radius:10px;padding:12px 16px;margin:0 0 18px;color:#7a2020;font-size:14px">{{ $detail }}</div>@endif
@include('emails.partials.rows', ['rows' => [
    'Order' => $order->reference,
    'State' => str_replace('_', ' ', $order->state),
    'Insurer' => $insurer,
    'Plan' => $order->quote?->plan_name,
    'Customer' => $order->customer?->name,
    'Email / mobile' => trim(($order->customer?->email ?? '').' · +'.($order->customer?->mobile ?? ''), ' ·+'),
    'Vehicle' => trim(($in['year'] ?? '').' '.($in['make'] ?? '').' '.($in['model'] ?? '')),
    'Total' => 'AED '.number_format((float) $order->total, 2),
    'Insurer request ID' => $order->request_id,
]])
<p style="margin:0 0 10px">@include('emails.partials.button', ['url' => $adminUrl, 'label' => 'Open the order'])</p>
<p style="margin:0;color:#7a8a99;font-size:13px">The customer sees a clear message on the website and can try again or call us.</p>
@endsection
