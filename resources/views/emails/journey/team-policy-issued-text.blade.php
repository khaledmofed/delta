New policy issued online

Policy number: {{ $policy?->policy_no }}
Insurer: {{ $insurer }}
Plan: {{ $order->quote?->plan_name }}
Customer: {{ $order->customer?->name }} · {{ $order->customer?->email }} · +{{ $order->customer?->mobile }}
Vehicle: {{ trim(($in['year'] ?? '').' '.($in['make'] ?? '').' '.($in['model'] ?? '')) }} · {{ $in['chassis'] ?? '' }}
Total: AED {{ number_format((float) $order->total, 2) }}
Order: {{ $order->reference }}

Admin: {{ $adminUrl }}
