You're insured!

{{ $insurer }} has issued your motor insurance policy.

Policy number: {{ $policy?->policy_no }}
Plan: {{ $order->quote?->plan_name }}
Vehicle: {{ trim(($in['year'] ?? '').' '.($in['make'] ?? '').' '.($in['model'] ?? '')) }}
Cover: {{ $policy?->starts_on?->format('d M Y') ?? '—' }} to {{ $policy?->ends_on?->format('d M Y') ?? '—' }}
Total paid: AED {{ number_format((float) ($policy?->total ?? $order->total), 2) }}
Order: {{ $order->reference }}

@if ($documentUrl)
Download policy document ({{ $days }} days): {{ $documentUrl }}
@else
View your policy: {{ $policyUrl }}
@endif
Track my order ({{ $order->reference }} + your email or mobile): {{ $trackUrl }}

Delta Insurance Services L.L.C · {{ config('site.phone') }} · {{ config('site.email') }}
