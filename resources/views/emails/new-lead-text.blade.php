@php($d = $lead->data ?? [])
New lead — {{ $lead->productName() }}

Reference: {{ $lead->reference }}
Name: {{ $lead->name }}
Mobile: +{{ $lead->mobile }}
Email: {{ $lead->email }}
Preferred contact: {{ $d['contact_pref'] ?? '—' }}
Emirate: {{ $d['emirate'] ?? '—' }}
Source: {{ ($d['source'] ?? '') === 'contact' ? 'Contact page' : 'Product page'.(! empty($d['page']) ? ' ('.$d['page'].')' : '') }}
Received: {{ $lead->created_at?->timezone(config('app.timezone'))->format('d M Y, H:i') }}
@if (! empty($d['message']))

Message:
{{ $d['message'] }}
@endif

Admin panel: {{ route('admin.leads', ['status' => 'new']) }}
Reply to this email to answer the customer directly.
