@extends('emails.layout')
@section('title', 'Your quote request')
@section('body')
<h1 style="margin:0 0 12px;font-size:22px;color:#122a43">Thank you, {{ \Illuminate\Support\Str::before($in['name_en'] ?? '', ' ') }}.</h1>
<p style="margin:0 0 16px">We have received your motor insurance quote request and sent it to our partner insurers. Your offers appear on the results page as soon as each insurer answers.</p>
@include('emails.partials.rows', ['rows' => [
    'Reference' => $qr->reference,
    'Vehicle' => trim(($in['year'] ?? '').' '.($in['make'] ?? '').' '.($in['model'] ?? '')),
    'Cover' => ($in['cover_type'] ?? '') === 'third_party' ? 'Third party' : 'Comprehensive',
    'Policy start' => isset($in['policy_start']) ? \Illuminate\Support\Carbon::parse($in['policy_start'])->format('d M Y') : null,
]])
<p style="margin:0 0 20px">@include('emails.partials.button', ['url' => $resultsUrl, 'label' => 'View your offers'])</p>
<p style="margin:0;color:#7a8a99;font-size:13px">This link works for {{ $days }} days, like the offers. If an insurer could not price your request online, an advisor can still help on {{ config('site.phone') }}.</p>
@endsection
