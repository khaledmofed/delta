@extends('layouts.site', ['active' => ''])

@section('title', 'Track my order — Delta Insurance')
@section('description', 'Check the status of your motor insurance order and download your policy.')

{{-- No customer accounts yet: order number + email or mobile opens the order (TrackController). Built from the journey components (d-flow, d-panel, d-field). --}}
@section('content')
        <section class="d-flow">
          <div class="container-x">
            <div class="d-flow__head">
              <div><span class="eyebrow">Motor Insurance · My order</span><h1 class="d-h2">Track my order</h1></div>
            </div>
            <div class="row g-4">
              <div class="col-xl-8">
                <form class="d-panel" method="post" action="{{ route('track.lookup') }}" novalidate>
                  @csrf
                  @if (session('notice'))
                    <div class="d-note mb-20" data-notice><i class="fal fa-circle-info"></i><div>{{ session('notice') }}</div></div>
                  @endif
                  @if ($errors->any())
                    @include('partials.motor.alert', ['title' => 'We can’t open this order', 'message' => $errors->first()])
                  @endif
                  <div class="d-panel__title"><i class="fal fa-magnifying-glass"></i><div><h2>Find your order</h2><p>Use the order number from your email and the email or mobile you gave us.</p></div></div>
                  <div class="row g-3">
                    <div class="col-md-6"><label class="d-field"><span>Order number <em>*</em></span><input class="d-input{{ $errors->has('reference') ? ' is-invalid' : '' }}" name="reference" required placeholder="ORD-2026-XXXXXXXX" value="{{ old('reference', $ref) }}" autocomplete="off" style="text-transform:uppercase" /><small class="d-error">Please enter your order number.</small></label></div>
                    <div class="col-md-6"><label class="d-field"><span>Email or mobile <em>*</em></span><input class="d-input{{ $errors->has('contact') ? ' is-invalid' : '' }}" name="contact" required placeholder="name@example.com or 50 123 4567" value="{{ old('contact') }}" autocomplete="email" /><small class="d-error">Please enter your email or mobile.</small></label></div>
                  </div>
                  <div class="d-actions">
                    <span class="d-muted-2" style="font-size:13px;align-self:center"><i class="fal fa-lock"></i> Only someone with both details can open the order.</span>
                    <button type="submit" class="d-btn d-btn--primary">Show my order <i class="far fa-arrow-right"></i></button>
                  </div>
                </form>
              </div>
              <div class="col-xl-4">
                <div class="d-aside">
                  <div class="d-card mb-3">
                    <h4><i class="fal fa-list-check me-2" style="color:var(--d-sky-300)"></i>What you can do here</h4>
                    <div class="d-kv"><span>Order status</span><b>Payment &amp; policy</b></div>
                    <div class="d-kv"><span>Policy document</span><b>Download</b></div>
                    <div class="d-kv"><span>Unpaid order</span><b>Complete payment</b></div>
                  </div>
                  <div class="d-note"><i class="fal fa-headset"></i><div>Can’t find your order number? Call us on <a class="d-link" href="tel:{{ config('site.phone_href') }}">{{ config('site.phone') }}</a> or <a class="d-link" href="{{ route('contact') }}">send us a message</a>.</div></div>
                </div>
              </div>
            </div>
          </div>
        </section>
@endsection
