@extends('layouts.site', ['active' => 'services'])

@section('title', 'Motor Insurance — Compare & Buy Online | Delta Insurance')
@section('description', 'Compare motor insurance offers from leading UAE insurers and buy online with Delta Insurance.')

@section('content')


        <section class="phero">
          <div class="container-x">
            <div class="phero-grid">
              <div>
                <div class="crumbs"><a href="{{ route('home') }}">Home</a><i class="far fa-chevron-right"></i><a href="{{ route('services') }}">Insurance</a><i class="far fa-chevron-right"></i><span>Motor Insurance</span></div>
                <span class="d-badge d-badge--online">Quote · compare · buy online</span>
                <h1 class="h-1 mt-20">Motor insurance — compare offers and buy online</h1>
                <p class="lead mt-20">Protect your vehicle with flexible insurance options. Get quotes from leading UAE insurers in one place, compare cover side by side and receive your policy digitally.</p>
                <div class="ctas"><a class="btn-x btn-navy" href="{{ url(config('site.links.motor_quote')) }}" ><span>Get a Motor Quote</span><span class="ar"><i class="far fa-arrow-right"></i></span></a><a class="btn-x btn-line  no-ar" href="#how" ><span>How it works</span></a></div>
              </div>
              <div class="ph cut"><img src="{{ asset('images/cut/car.webp') }}" alt="Motor Insurance" onerror="this.outerHTML='<i class=&quot;fal fa-car-side big-ic&quot;></i>'" /></div>
            </div>
          </div>
        </section>
        <section class="sec" id="how">
          <div class="container-x">
            <div class="sec-head"><div><span class="eyebrow">Choose your cover</span><h2 class="h-2">Comprehensive or third party?</h2></div><p class="lead">Not sure which you need? The comparison shows exactly what each offer includes before you buy.</p></div>
            <div class="row g-4">
              <div class="col-md-6"><div class="card-x navy h-100"><i class="fal fa-shield-check" style="font-size:30px;color:var(--mint)"></i><h3 class="h-3 mt-20 mb-15">Comprehensive</h3><ul class="check-list" style="color:#c9d6e2"><li><i class="fas fa-check"></i>Damage to your own vehicle</li><li><i class="fas fa-check"></i>Theft and fire</li><li><i class="fas fa-check"></i>Third-party liability</li><li><i class="fas fa-check"></i>Agency or garage repair</li><li><i class="fas fa-check"></i>Optional roadside, rent-a-car, GCC cover</li></ul></div></div>
              <div class="col-md-6"><div class="card-x h-100"><i class="fal fa-car-burst" style="font-size:30px;color:var(--steel)"></i><h3 class="h-3 mt-20 mb-15">Third Party Liability</h3><ul class="check-list"><li><i class="fas fa-check"></i>Damage to other vehicles and property</li><li><i class="fas fa-check"></i>Injury to third parties</li><li><i class="fas fa-check"></i>Meets UAE legal requirements</li><li><i class="fas fa-check"></i>Optional personal accident cover</li><li><i class="fas fa-check"></i>Lower premium</li></ul></div></div>
            </div>
            <div class="steps-x mt-60">
              <div><span class="n">01</span><i class="fal fa-user"></i><h4>Your details</h4><p>Name, contact, age and driving history.</p></div>
              <div><span class="n">02</span><i class="fal fa-car"></i><h4>Your vehicle</h4><p>Make, model, year and value.</p></div>
              <div><span class="n">03</span><i class="fal fa-scale-balanced"></i><h4>Compare offers</h4><p>Sukoon, Orient and ADNIC at once.</p></div>
              <div><span class="n">04</span><i class="fal fa-file-arrow-up"></i><h4>Upload &amp; pay</h4><p>Emirates ID, licence, Mulkiya.</p></div>
              <div><span class="n">05</span><i class="fal fa-file-certificate"></i><h4>Policy issued</h4><p>Download from your account.</p></div>
            </div>
          </div>
        </section>
        <section class="sec bg-soft">
          <div class="container-x">
            <div class="split" style="align-items:start">
              <div>
                <span class="eyebrow">What you will need</span>
                <h2 class="h-2">Have these documents ready</h2>
                <ul class="check-list mt-25">
                  <li><i class="fas fa-check"></i><span><b>Emirates ID</b> — front and back</span></li>
                  <li><i class="fas fa-check"></i><span><b>UAE driving licence</b></span></li>
                  <li><i class="fas fa-check"></i><span><b>Vehicle registration (Mulkiya)</b> — or dealer / customs certificate for a new car</span></li>
                </ul>
                <div class="d-note mt-25"><i class="fal fa-lock"></i><div>Documents are stored in private, encrypted storage and shared only with the insurer you choose.</div></div>
              </div>
              <form class="d-card d-card--glow" data-quick-quote style="padding:34px">
                <span class="d-badge d-badge--online mb-15">Start online</span>
                <h3 class="d-h3 mb-10 mt-10">Get your motor quote</h3>
                <p class="d-muted-2 mb-20">Takes about 3 minutes. No commitment.</p>
                <div class="row g-2">
                  <div class="col-md-4"><select class="d-select" name="make" aria-label="Make"></select></div>
                  <div class="col-md-4"><select class="d-select" name="model" aria-label="Model"><option value="">Model</option></select></div>
                  <div class="col-md-4"><select class="d-select" name="year" aria-label="Year"></select></div>
                  <div class="col-12 mt-10"><button class="btn-x btn-navy" style="width:100%" type="submit"><span>Get my quotes</span><span class="ar"><i class="far fa-arrow-right"></i></span></button></div>
                </div>
              </form>
            </div>
          </div>
        </section>
        <section class="sec">
          <div class="container-x" style="max-width:920px">
            <div class="center-head" style="margin-bottom:36px"><span class="eyebrow">FAQ</span><h2 class="h-2">Motor insurance questions</h2></div>
            <div class="faq-x">
              <details open><summary>Which insurers do you compare?</summary><p>In this first phase we connect to Sukoon Insurance, Orient Insurance and ADNIC. More insurers will be added over time.</p></details>
              <details><summary>How is the price calculated?</summary><p>Each insurer prices your policy using your vehicle, its value, your age, driving experience and claims history. Prices include 5% VAT.</p></details>
              <details><summary>When does my policy start?</summary><p>You choose the start date. Once payment is complete the insurer issues your policy and we send it by email and to your Delta account.</p></details>
              <details><summary>What if my car is older than 5 years?</summary><p>Agency repair is usually available for newer cars only. For older vehicles you will see garage repair and third-party options.</p></details>
            </div>
          </div>
        </section>
@endsection
