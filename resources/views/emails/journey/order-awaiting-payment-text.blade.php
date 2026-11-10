Your order is ready for payment

Order: {{ $order->reference }}
Insurer: {{ $insurer }}
Plan: {{ $order->quote?->plan_name }}
Vehicle: {{ trim(($in['year'] ?? '').' '.($in['make'] ?? '').' '.($in['model'] ?? '')) }}
Total to pay: AED {{ number_format((float) $order->total, 2) }} (incl. VAT)

Complete payment: {{ $payUrl }}

Already paid? We confirm every payment directly with the insurer.
Track my order: {{ $trackUrl }}

Delta Insurance Services L.L.C · {{ config('site.phone') }} · {{ config('site.email') }}
