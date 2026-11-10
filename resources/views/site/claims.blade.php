@extends('layouts.site', ['active' => 'claims'])

@section('title', 'Claims & Support — Delta Insurance Services')
@section('description', 'How to make an insurance claim, emergency numbers in the UAE and help from Delta Insurance advisors.')

{{-- Claims & Support (deck: top-level menu item). Built only from existing site components:
     phero, values, steps-x, check-list, card-x navy, faq-x and the lead form. Help requests are saved as leads (product "claims"). --}}
@php
    $insurerLines = collect(config('insurers.providers'))->filter(fn ($c) => filled($c['claims_phone'] ?? null));
@endphp

@section('content')
        <section class="phero">
          <div class="container-x">
            <div class="phero-grid">
              <div>
                <div class="crumbs"><a href="{{ route('home') }}">Home</a><i class="far fa-chevron-right"></i><span>Claims &amp; Support</span></div>
                <span class="eyebrow">Claims &amp; Support</span>
                <h1 class="h-1">We’re with you when you need to claim</h1>
                <p class="lead mt-20">As your broker, Delta helps you report a claim, prepare the documents and follow it up with your insurer — from the first call to the settlement.</p>
                <div class="d-flex gap-2 flex-wrap mt-30"><a class="btn-x btn-navy" href="#help"><span>Request help</span><span class="ar"><i class="far fa-arrow-right"></i></span></a><a class="btn-x btn-line no-ar" href="tel:{{ config('site.phone_href') }}"><span>Call {{ config('site.phone') }}</span></a></div>
              </div>
              <div class="ph"><img src="{{ asset('images/photo/handshake.jpg') }}" alt="A Delta advisor helping a customer" /></div>
            </div>
          </div>
        </section>

        <section class="sec">
          <div class="container-x">
            <div class="center-head"><span class="eyebrow">In an emergency</span><h2 class="h-2">First, make sure everyone is safe</h2><p class="lead mt-15">If anyone is hurt or the road is blocked, call the emergency services before anything else.</p></div>
            <div class="values">
              @foreach (config('site.claims.emergency') as [$number, $label, $icon])
              <div><i class="fal {{ $icon }}"></i><h4><a href="tel:{{ $number }}" style="color:inherit">{{ $number }}</a></h4><p>{{ $label }}</p></div>
              @endforeach
              <div><i class="fal fa-headset"></i><h4><a href="tel:{{ config('site.phone_href') }}" style="color:inherit">{{ config('site.phone') }}</a></h4><p>Delta Insurance — claims help</p></div>
            </div>
          </div>
        </section>

        <section class="sec bg-soft">
          <div class="container-x">
            <div class="center-head"><span class="eyebrow">Motor claims</span><h2 class="h-2">How to make a claim</h2></div>
            <div class="steps-x mt-60">
              <div><span class="n">01</span><i class="fal fa-file-shield"></i><h4>Get a police report</h4><p>For any accident in the UAE, report it to the police and keep the report.</p></div>
              <div><span class="n">02</span><i class="fal fa-camera"></i><h4>Collect the details</h4><p>Photos of the damage, the other driver’s details and your documents.</p></div>
              <div><span class="n">03</span><i class="fal fa-headset"></i><h4>Tell us</h4><p>Call Delta or send the form below — we notify your insurer with you.</p></div>
              <div><span class="n">04</span><i class="fal fa-screwdriver-wrench"></i><h4>Inspection &amp; repair</h4><p>The insurer assesses the damage and approves the repair under your policy.</p></div>
              <div><span class="n">05</span><i class="fal fa-circle-check"></i><h4>Settlement</h4><p>Your car is repaired or the claim is paid, less any policy excess.</p></div>
            </div>
          </div>
        </section>

        <section class="sec">
          <div class="container-x">
            <div class="row g-5">
              <div class="col-lg-6">
                <span class="eyebrow">Be prepared</span>
                <h2 class="h-2 mb-30">Documents you usually need</h2>
                <ul class="check-list">
                  <li><i class="fas fa-check"></i><span><b>Police report</b> — for accidents, theft and vandalism.</span></li>
                  <li><i class="fas fa-check"></i><span><b>Policy number</b> — on your policy schedule and in our emails.</span></li>
                  <li><i class="fas fa-check"></i><span><b>Driving licence &amp; Emirates ID</b> — of the driver at the time.</span></li>
                  <li><i class="fas fa-check"></i><span><b>Vehicle registration (Mulkiya)</b></span></li>
                  <li><i class="fas fa-check"></i><span><b>Photos</b> — of the damage and the accident scene.</span></li>
                </ul>
                <p class="d-muted-2 mt-20 mb-0">Your insurer may ask for more depending on the claim. We tell you exactly what is needed.</p>
              </div>
              <div class="col-lg-6">
                <span class="eyebrow">Insurer claims lines</span>
                <h2 class="h-2 mb-30">Reach your insurer</h2>
                @forelse ($insurerLines as $code => $cfg)
                  <div class="d-card mb-3" style="padding:20px 22px"><div class="d-flex align-items-center gap-3">@include('partials.motor.logo', ['ins' => \App\Support\MotorPresenter::insurer($code), 'size' => 'lg'])<div style="flex:1"><b style="color:var(--ink);display:block">{{ $cfg['name'] }}</b><small class="d-muted-2">Claims line</small></div><a class="btn-x btn-line no-ar sm" href="tel:{{ preg_replace('/[^\d+]/', '', $cfg['claims_phone']) }}"><span>{{ $cfg['claims_phone'] }}</span></a></div></div>
                @empty
                  <div class="d-note"><i class="fal fa-circle-info"></i><div>Call Delta on <a class="d-link" href="tel:{{ config('site.phone_href') }}">{{ config('site.phone') }}</a> and we connect you with your insurer’s claims team. Your insurer’s claims number is also printed on your policy schedule.</div></div>
                @endforelse
                <div class="faq-x mt-30">
                  <details open><summary>Do I need to contact the insurer myself?</summary><p>You can, but you don’t have to. As your broker we notify the insurer with you and follow the claim until it is settled.</p></details>
                  <details><summary>Where do I find my policy number?</summary><p>On your policy schedule and in the confirmation emails from Delta. You can also find it with <a class="d-link" href="{{ route('track') }}">Track my order</a>.</p></details>
                  <details><summary>Will a claim affect my renewal?</summary><p>It can affect your no-claims years and the next premium. Your advisor explains the impact before you decide.</p></details>
                </div>
              </div>
            </div>
          </div>
        </section>

        <section class="sec bg-soft" id="help">
          <div class="container-x">
            <div class="row g-4">
              <div class="col-lg-5">
                <div class="card-x navy h-100" style="padding:40px">
                  <h3 class="h-3 mb-20">Claims &amp; policy help</h3>
                  <ul class="check-list" style="color:#c9d6e2">
                    <li><i class="fas fa-phone" style="background:rgba(0,255,151,.12);color:var(--mint)"></i><span><b style="color:#fff">Phone</b><br/><a href="tel:{{ config('site.phone_href') }}">{{ config('site.phone') }}</a></span></li>
                    <li><i class="fas fa-envelope" style="background:rgba(0,255,151,.12);color:var(--mint)"></i><span><b style="color:#fff">Email</b><br/><a href="mailto:{{ config('site.email') }}">{{ config('site.email') }}</a></span></li>
                    <li><i class="fas fa-file-certificate" style="background:rgba(0,255,151,.12);color:var(--mint)"></i><span><b style="color:#fff">Your policy online</b><br/>Status and documents on <a href="{{ route('track') }}">Track my order</a>.</span></li>
                  </ul>
                </div>
              </div>
              <div class="col-lg-7">
                <form class="d-card d-card--glow" data-lead-form method="post" action="{{ route('leads.store') }}" novalidate style="padding:40px">
                  @include('partials.site.lead-fields', ['source' => 'claims'])
                  <input type="hidden" name="product" value="claims" />
                  <h3 class="d-h3 mb-10">Request help</h3>
                  <p class="d-muted-2 mb-25">Tell us what happened or what you need. An advisor will call you back.</p>
                  <div class="row g-3">
                    <div class="col-md-6"><label class="d-field"><span>Full name <em>*</em></span><input class="d-input" name="name" required /></label></div>
                    <div class="col-md-6"><label class="d-field"><span>Mobile <em>*</em></span><div class="d-input-group"><span class="pre">+971</span><input class="d-input" name="mobile" required placeholder="50 123 4567" /></div></label></div>
                    <div class="col-md-6"><label class="d-field"><span>Email <em>*</em></span><input class="d-input" type="email" name="email" required /></label></div>
                    <div class="col-md-6"><label class="d-field"><span>I need help with <em>*</em></span><select class="d-select" name="requestType" required><option value="">Select</option>@foreach (config('site.claims.request_types') as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach</select></label></div>
                    <div class="col-md-6"><label class="d-field"><span>Policy number</span><input class="d-input" name="policyNo" placeholder="If you have it" style="text-transform:uppercase" /></label></div>
                    <div class="col-md-6"><label class="d-field"><span>Insurer</span><select class="d-select" name="insurer"><option value="">Not sure</option>@foreach (config('insurers.providers') as $code => $cfg)<option value="{{ $code }}">{{ $cfg['name'] }}</option>@endforeach<option value="other">Other insurer</option></select></label></div>
                    <div class="col-12"><label class="d-field"><span>What happened? <em>*</em></span><textarea class="d-textarea" name="message" required placeholder="Date, place and a short description"></textarea></label></div>
                    <div class="col-12"><label class="d-check"><input type="checkbox" name="consent" required /> I agree to be contacted by Delta Insurance about this request.</label></div>
                    <div class="col-12"><button class="btn-x btn-navy" type="submit"><span>Send request</span><span class="ar"><i class="far fa-arrow-right"></i></span></button></div>
                  </div>
                </form>
              </div>
            </div>
          </div>
        </section>
@endsection
