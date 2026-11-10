@php($d = $lead->data ?? [])
Thank you, {{ \Illuminate\Support\Str::before($lead->name, ' ') }}.

We have received your request for {{ $lead->productName() }}. A Delta advisor will contact you{{ ! empty($d['contact_pref']) ? ' by '.strtolower($d['contact_pref']) : '' }} to discuss your options.

Your reference: {{ $lead->reference }}
Mobile: +{{ $lead->mobile }}
@foreach ($lead->supportDetails() as $k => $v)
{{ $k }}: {{ $v }}
@endforeach
@if (! empty($d['emirate']))
Emirate: {{ $d['emirate'] }}
@endif
@if (! empty($d['message']))
Your message:
{{ $d['message'] }}
@endif

Please quote your reference if you contact us: {{ config('site.phone') }} · {{ config('site.email') }}

Delta Insurance Services L.L.C · {{ config('site.location') }}
