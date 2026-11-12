@extends('emails.layout')
@section('title', 'Complete your payment')
@section('body')
<h1 style="margin:0 0 12px;font-size:22px;color:#122a43">Your order is ready for payment</h1>
<p style="margin:0 0 16px">You chose the offer below. Payment is made on {{ $insurer }}’s secure payment page; your policy is issued as soon as the insurer confirms the payment.</p>
@include('emails.partials.rows', ['rows' => [
    'Order' => $order->reference,
    'Insurer' => $insurer,
    'Plan' => $order->quote?->plan_name,
    'Vehicle' => trim(($in['year'] ?? '').' '.($in['make'] ?? '').' '.($in['model'] ?? '')),
    'Policy start' => $order->policy_start?->format('d M Y'),
    'Total to pay' => 'AED '.number_format((float) $order->total, 2).' (incl. VAT)',
]])
<p style="margin:0 0 20px">@include('emails.partials.button', ['url' => $payUrl, 'label' => 'Complete payment'])</p>
@include('emails.partials.track')
<p style="margin:0;color:#7a8a99;font-size:13px">Already paid? You don’t need to do anything — we confirm every payment directly with the insurer.</p>
@endsection
