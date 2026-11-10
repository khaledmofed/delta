{{ $what }}
@if ($detail)
{{ $detail }}
@endif

Order: {{ $order->reference }} ({{ $order->state }})
Insurer: {{ $insurer }} · request ID {{ $order->request_id }}
Customer: {{ $order->customer?->name }} · {{ $order->customer?->email }} · +{{ $order->customer?->mobile }}
Total: AED {{ number_format((float) $order->total, 2) }}

Admin: {{ $adminUrl }}
