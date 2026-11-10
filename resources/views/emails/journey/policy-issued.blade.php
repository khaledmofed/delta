@extends('emails.layout')
@section('title', 'You’re insured')
@section('body')
<h1 style="margin:0 0 12px;font-size:22px;color:#122a43">You’re insured!</h1>
<p style="margin:0 0 16px">{{ $insurer }} has issued your motor insurance policy. Please keep this email for your records.</p>
@include('emails.partials.rows', ['rows' => [
    'Policy number' => $policy?->policy_no,
    'Insurer' => $insurer,
    'Plan' => $order->quote?->plan_name,
    'Vehicle' => trim(($in['year'] ?? '').' '.($in['make'] ?? '').' '.($in['model'] ?? '')),
    'Cover' => ($policy?->starts_on?->format('d M Y') ?? '—').' → '.($policy?->ends_on?->format('d M Y') ?? '—'),
    'Total paid' => 'AED '.number_format((float) ($policy?->total ?? $order->total), 2),
    'Order' => $order->reference,
]])
<p style="margin:0 0 20px">@if ($documentUrl)@include('emails.partials.button', ['url' => $documentUrl, 'label' => 'Download policy document'])@else@include('emails.partials.button', ['url' => $policyUrl, 'label' => 'View your policy'])@endif</p>
<p style="margin:0;color:#7a8a99;font-size:13px">@if ($documentUrl)The download link works for {{ $days }} days. @else The insurer’s policy documents will be sent to you separately. @endif You can always see your policy on <a href="{{ $trackUrl }}" style="color:#256383">Track my order</a> with your order number and email.</p>
@endsection
