@extends('layouts.site', ['active' => 'services'])

@section('title', 'Insurance Services — Delta Insurance Services')
@section('description', 'Motor, medical, home, travel, boat and pet insurance from Delta Insurance, Sharjah.')

@section('content')


        <section class="phero">
          <div class="container-x">
            <div class="crumbs"><a href="{{ route('home') }}">Home</a><i class="far fa-chevron-right"></i><span>Insurance</span></div>
            <div class="sec-head" style="margin:0"><div><span class="eyebrow">Our services</span><h1 class="h-1">Insurance for the things<br/>that matter to you</h1></div><p class="lead">Six personal insurance lines from one broker. Motor insurance is available fully online; for everything else an advisor prepares your quote.</p></div>
          </div>
        </section>
        <section class="sec" style="padding-top:60px">
          <div class="container-x"><div class="svc-grid">
          <a class="svc feat rv" href="{{ route('products.motor') }}">
            <div class="body">
              <span class="d-badge d-badge--online" style="align-self:flex-start;margin-bottom:22px">Available online</span>
              <h3>Motor Insurance</h3>
              <p>Protect your vehicle with flexible insurance options. Compare offers from leading UAE insurers side by side and buy your policy online.</p>
              <div class="steps"><span><i class="fas fa-check"></i>Instant quotes</span><span><i class="fas fa-check"></i>{{ \App\Models\Insurer::comparedLabel() }}</span><span><i class="fas fa-check"></i>Policy issued online</span></div>
              <div class="d-flex gap-2 flex-wrap"><span class="btn-x btn-mint"><span>Get a Motor Quote</span><span class="ar"><i class="far fa-arrow-right"></i></span></span></div>
            </div>
            <div class="art"><img src="{{ asset('images/cut/car.webp') }}" alt="Car" /></div>
          </a>
          <a class="svc rv" href="{{ route('products.medical') }}">
            <div class="art "><img src="{{ asset('images/cut/family.webp') }}" alt="Medical Insurance" onerror="this.outerHTML='<i class=&quot;fal fa-heart-pulse big-ic&quot;></i>'" /></div>
            <div class="body">
              <h3>Medical Insurance</h3>
              <p>Health cover designed around your needs — for individuals, families and employees.</p>
              <div class="foot"><span class="d-badge d-badge--advisor">Quote by an advisor</span><span class="arr"><i class="far fa-arrow-right"></i></span></div>
            </div>
          </a>
          <a class="svc rv" href="{{ route('products.home') }}">
            <div class="art "><img src="{{ asset('images/cut/villa.webp') }}" alt="Home Insurance" onerror="this.outerHTML='<i class=&quot;fal fa-house-heart big-ic&quot;></i>'" /></div>
            <div class="body">
              <h3>Home Insurance</h3>
              <p>Protect your home and valuable belongings against the unexpected.</p>
              <div class="foot"><span class="d-badge d-badge--advisor">Quote by an advisor</span><span class="arr"><i class="far fa-arrow-right"></i></span></div>
            </div>
          </a>
          <a class="svc rv" href="{{ route('products.travel') }}">
            <div class="art "><img src="{{ asset('images/cut/travel.webp') }}" alt="Travel Insurance" onerror="this.outerHTML='<i class=&quot;fal fa-plane-departure big-ic&quot;></i>'" /></div>
            <div class="body">
              <h3>Travel Insurance</h3>
              <p>Medical and trip protection for every journey, with visa-ready options.</p>
              <div class="foot"><span class="d-badge d-badge--advisor">Quote by an advisor</span><span class="arr"><i class="far fa-arrow-right"></i></span></div>
            </div>
          </a>
          <a class="svc rv" href="{{ route('products.boat') }}">
            <div class="art photo"><img src="{{ asset('images/photo/boat.jpg') }}" alt="Boat Insurance" onerror="this.outerHTML='<i class=&quot;fal fa-sailboat big-ic&quot;></i>'" /></div>
            <div class="body">
              <h3>Boat Insurance</h3>
              <p>Insurance solutions for your boat, yacht or jet ski in UAE waters.</p>
              <div class="foot"><span class="d-badge d-badge--advisor">Quote by an advisor</span><span class="arr"><i class="far fa-arrow-right"></i></span></div>
            </div>
          </a>
          <a class="svc rv" href="{{ route('products.pet') }}">
            <div class="art photo"><img src="{{ asset('images/photo/pet.jpg') }}" alt="Pet Insurance" onerror="this.outerHTML='<i class=&quot;fal fa-paw big-ic&quot;></i>'" /></div>
            <div class="body">
              <h3>Pet Insurance</h3>
              <p>Coverage options that help you care for your cats and dogs.</p>
              <div class="foot"><span class="d-badge d-badge--advisor">Quote by an advisor</span><span class="arr"><i class="far fa-arrow-right"></i></span></div>
            </div>
          </a>
          <div class="svc-cta rv">
            <img class="mk" src="{{ asset('images/brand/delta-glyph.svg') }}" alt="" aria-hidden="true" />
            <span class="eyebrow">Not sure what you need?</span>
            <h3>Talk to an advisor about the right cover for you.</h3>
            <p>We will review your situation, compare suitable plans from our insurance partners and explain the differences — at no extra cost.</p>
            <div class="d-flex gap-2 flex-wrap mt-10"><a class="btn-x btn-navy" href="{{ route('contact') }}" ><span>Speak to an advisor</span><span class="ar"><i class="far fa-arrow-right"></i></span></a><a class="btn-x btn-line  no-ar" href="tel:+97165747420" ><span>+971 6 574 7420</span></a></div>
          </div></div></div>
        </section>
@endsection
