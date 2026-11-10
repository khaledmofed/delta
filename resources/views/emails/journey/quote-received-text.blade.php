Thank you, {{ \Illuminate\Support\Str::before($in['name_en'] ?? '', ' ') }}.

We have received your motor insurance quote request and sent it to our partner insurers.

Reference: {{ $qr->reference }}
Vehicle: {{ trim(($in['year'] ?? '').' '.($in['make'] ?? '').' '.($in['model'] ?? '')) }}
Cover: {{ ($in['cover_type'] ?? '') === 'third_party' ? 'Third party' : 'Comprehensive' }}

View your offers ({{ $days }} days): {{ $resultsUrl }}

Delta Insurance Services L.L.C · {{ config('site.phone') }} · {{ config('site.email') }}
