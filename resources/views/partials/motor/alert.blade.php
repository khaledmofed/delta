{{-- Insurer / checkout error, styled like the wizard's eligibility box. --}}
@if ($errors->has('checkout') || ($message ?? null))
<div class="d-elig mb-20"><div class="d-elig__box"><i class="fal fa-triangle-exclamation"></i><div><b>{{ $title ?? 'We couldn’t continue to payment' }}</b><ul><li>{{ $message ?? $errors->first('checkout') }}</li></ul><span>Our advisors can help — <a class="d-link" href="{{ route('contact') }}">request a call back</a> or call <a class="d-link" href="tel:{{ config('site.phone_href') }}">{{ config('site.phone') }}</a>.</span></div></div></div>
@endif
