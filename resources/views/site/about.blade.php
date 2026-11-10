@extends('layouts.site', ['active' => 'about'])

@section('title', 'About Us — Delta Insurance Services')
@section('description', 'Delta Insurance Services L.L.C — insurance broker in Sharjah, UAE since 1990.')

@section('content')


        <section class="phero">
          <div class="container-x">
            <div class="phero-grid">
              <div>
                <div class="crumbs"><a href="{{ route('home') }}">Home</a><i class="far fa-chevron-right"></i><span>About Us</span></div>
                <span class="eyebrow">About Delta</span>
                <h1 class="h-1">Your trusted insurance broker since 1990</h1>
                <p class="lead mt-20">Delta Insurance Services L.L.C is an insurance broker based in Sharjah, United Arab Emirates. For more than three decades we have provided insurance solutions to individuals, families and businesses.</p>
              </div>
              <div class="ph"><img src="{{ asset('images/photo/handshake.jpg') }}" alt="Delta advisors" /></div>
            </div>
          </div>
        </section>
        <section class="sec">
          <div class="container-x">
            <div class="split">
              <div class="photo-stack">
                <div class="main"><img src="{{ asset('images/photo/team.jpg') }}" alt="Delta team at work" /></div>
                <div class="badge-yr"><b>35+</b><span>years of insurance experience</span></div>
              </div>
              <div>
                <span class="eyebrow">What we do</span>
                <h2 class="h-2">Flexible and affordable insurance, explained clearly</h2>
                <p class="lead mt-20">As a broker we work with several insurance companies. That means we can compare options for you, explain the differences in cover and price, and recommend what fits your situation.</p>
                <ul class="check-list mt-25">
                  <li><i class="fas fa-check"></i><span><b>Advice first</b> — we listen before we recommend.</span></li>
                  <li><i class="fas fa-check"></i><span><b>Choice of insurers</b> — options from different companies, not just one.</span></li>
                  <li><i class="fas fa-check"></i><span><b>Support after purchase</b> — renewals, changes and claims.</span></li>
                  <li><i class="fas fa-check"></i><span><b>Digital where it helps</b> — motor insurance fully online, more products to follow.</span></li>
                </ul>
              </div>
            </div>
          </div>
        </section>
        <section class="sec bg-soft">
          <div class="container-x">
            <div class="center-head"><span class="eyebrow">Our values</span><h2 class="h-2">How we work</h2></div>
            <div class="values">
              <div><i class="fal fa-handshake"></i><h4>Trust</h4><p>Honest advice and transparent pricing.</p></div>
              <div><i class="fal fa-user-headset"></i><h4>Service</h4><p>Real people who know your policy.</p></div>
              <div><i class="fal fa-scale-balanced"></i><h4>Choice</h4><p>Options from multiple insurers.</p></div>
              <div><i class="fal fa-shield-check"></i><h4>Security</h4><p>Your data protected at every step.</p></div>
            </div>
          </div>
        </section>
        <section class="sec-sm">
          <div class="container-x">
            <div class="cta-x">
              <img class="mk" src="{{ asset('images/brand/delta-glyph-white.svg') }}" alt="" aria-hidden="true" />
              <div><span class="eyebrow on-dark">Let’s talk</span><h2 class="h-1">Find the right cover with Delta</h2><p class="lead mt-15" style="color:#c2d1de">Explore our services or speak to an advisor today.</p></div>
              <div class="d-flex gap-2 flex-wrap justify-content-lg-end"><a class="btn-x btn-mint" href="{{ route('services') }}" ><span>Explore services</span><span class="ar"><i class="far fa-arrow-right"></i></span></a><a class="btn-x btn-ghost-dark  no-ar" href="{{ route('contact') }}" ><span>Contact us</span></a></div>
            </div>
          </div>
        </section>
@endsection
