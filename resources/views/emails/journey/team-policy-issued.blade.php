@extends('emails.layout')
@section('title', 'New policy')
@section('body')
<h1 style="margin:0 0 12px;font-size:20px;color:#122a43">New policy issued online</h1>
@include('emails.partials.rows', ['rows' => [
    'Policy number' => $policy?->policy_no,
    'Insurer' => $insurer,
    'Plan' => $order->quote?->plan_name,
    'Customer' => $order->customer?->name,
    'Email / mobile' => trim(($order->customer?->email ?? '').' · +'.($order->customer?->mobile ?? ''), ' ·+'),
    'Vehicle' => trim(($in['year'] ?? '').' '.($in['make'] ?? '').' '.($in['model'] ?? '').' · '.($in['chassis'] ?? ''), ' ·'),
    'Cover' => ($policy?->starts_on?->format('d M Y') ?? '—').' → '.($policy?->ends_on?->format('d M Y') ?? '—'),
    'Premium / VAT / total' => 'AED '.number_format((float) $order->premium, 2).' / '.number_format((float) $order->vat, 2).' / '.number_format((float) $order->total, 2),
    'Order' => $order->reference,
]])
<p style="margin:0">@include('emails.partials.button', ['url' => $adminUrl, 'label' => 'Open the order'])</p>
@endsection
