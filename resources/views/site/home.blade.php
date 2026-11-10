@extends('layouts.site', ['active' => 'home'])

@section('title', 'Delta Insurance Services — Insurance Brokers in Sharjah, UAE')
@section('description', 'Delta Insurance Services L.L.C — trusted insurance broker in Sharjah since 1990. Motor, medical, home, travel, boat and pet insurance.')

@section('content')

      <!-- HERO -->
      <section class="hero">
        <div class="container-x">
          <div class="hero-grid">
            <div>
              <span class="kicker rv"
                ><b>NEW</b> Motor insurance is now available online</span
              >
              <h1 class="h-display rv" style="text-transform: capitalize">
                Insurance made clear, for <span class="hl">every part</span> of
                your life.
              </h1>
              <p class="lead rv">
                Delta Insurance Services is a trusted insurance broker in
                Sharjah. Since 1990 we have helped people across the UAE find
                flexible and affordable cover for their car, health, home,
                travels, boats and pets.
              </p>
              <div class="ctas rv">
                <a class="btn-x btn-navy" href="{{ route('services') }}"
                  ><span>Explore our services</span
                  ><span class="ar"><i class="far fa-arrow-right"></i></span></a
                ><a class="btn-x btn-line no-ar" href="{{ route('contact') }}"
                  ><span>Speak to an advisor</span></a
                >
              </div>
              <div class="trust rv">
                <div><i class="fal fa-award"></i>Established 1990</div>
                <div>
                  <i class="fal fa-scale-balanced"></i>Independent broker
                </div>
                <div><i class="fal fa-headset"></i>Real advisors</div>
              </div>
            </div>
            <div class="hero-visual rv">
              <div class="photo">
                <img
                  src="{{ asset('images/photo/hero-advisors.jpg') }}"
                  alt="Delta advisor helping a client"
                />
              </div>
              <div class="float-card b">
                <span class="yr">35+</span>
                <div>
                  <b style="display: block; font-size: 14px">Years in the UAE</b
                  ><small>Serving clients from Sharjah</small>
                </div>
              </div>
              <div class="float-card a">
                <small style="margin-bottom: 6px"
                  >Motor quotes · 2024 Toyota Land Cruiser</small
                >
                <div class="row-mini">
                  <span class="ins-logo ins-logo--sm" title="Orient Insurance"
                    ><img
                      src="{{ asset('images/insurers/orient.png') }}"
                      alt="Orient Insurance"
                      data-pid="orient"
                      onerror="
                        DELTA.util && DELTA.util.logoFallback(this)
                      " /></span
                  >Orient<span class="p">AED 6,812</span>
                </div>
                <div class="row-mini">
                  <span class="ins-logo ins-logo--sm" title="Sukoon Insurance"
                    ><img
                      src="{{ asset('images/insurers/sukoon.png') }}"
                      alt="Sukoon Insurance"
                      data-pid="sukoon"
                      onerror="
                        DELTA.util && DELTA.util.logoFallback(this)
                      " /></span
                  >Sukoon<span class="p">AED 7,164</span>
                </div>
                <div class="row-mini">
                  <span class="ins-logo ins-logo--sm" title="ADNIC"
                    ><img
                      src="{{ asset('images/insurers/adnic.png') }}"
                      alt="ADNIC"
                      data-pid="adnic"
                      onerror="
                        DELTA.util && DELTA.util.logoFallback(this)
                      " /></span
                  >ADNIC<span class="p">AED 7,590</span>
                </div>
              </div>
            </div>
          </div>
          <div class="quotebar rv" id="quote">
            <div class="t">
              <span
                class="d-badge d-badge--online"
                style="background: rgba(0, 255, 151, 0.12); color: #00ff97"
                >Online now</span
              ><b>Compare motor insurance</b
              ><small>Quotes from 3 insurers in minutes</small>
            </div>
            <form data-quick-quote>
              <select name="make" aria-label="Make"></select>
              <select name="model" aria-label="Model">
                <option value="">Model</option>
              </select>
              <select name="year" aria-label="Year"></select>
              <button class="btn-x btn-mint" type="submit">
                <span>Get my quotes</span
                ><span class="ar"><i class="far fa-arrow-right"></i></span>
              </button>
            </form>
          </div>
          <div class="insurers">
            <span
              >Offers from leading UAE insurers<br /><small
                style="color: #b23b3b"
                >demo — to be confirmed</small
              ></span
            >
            <div class="list">
              <span class="ins"
                ><span class="ins-logo ins-logo--sm" title="Sukoon Insurance"
                  ><img
                    src="{{ asset('images/insurers/sukoon.png') }}"
                    alt="Sukoon Insurance"
                    data-pid="sukoon"
                    onerror="
                      DELTA.util && DELTA.util.logoFallback(this)
                    " /></span
                ><span class="nm">Sukoon Insurance</span></span
              ><span class="ins"
                ><span class="ins-logo ins-logo--sm" title="Orient Insurance"
                  ><img
                    src="{{ asset('images/insurers/orient.png') }}"
                    alt="Orient Insurance"
                    data-pid="orient"
                    onerror="
                      DELTA.util && DELTA.util.logoFallback(this)
                    " /></span
                ><span class="nm">Orient Insurance</span></span
              ><span class="ins"
                ><span class="ins-logo ins-logo--sm" title="ADNIC"
                  ><img
                    src="{{ asset('images/insurers/adnic.png') }}"
                    alt="ADNIC"
                    data-pid="adnic"
                    onerror="
                      DELTA.util && DELTA.util.logoFallback(this)
                    " /></span
                ><span class="nm">ADNIC</span></span
              ><span class="ins"
                ><span class="ins-logo ins-logo--sm" title="GIG Gulf"
                  ><img
                    src="{{ asset('images/insurers/gig.png') }}"
                    alt="GIG Gulf"
                    data-pid="gig"
                    onerror="
                      DELTA.util && DELTA.util.logoFallback(this)
                    " /></span
                ><span class="nm">GIG Gulf</span></span
              ><span class="ins"
                ><span
                  class="ins-logo ins-logo--sm"
                  title="Al Buhaira National Insurance"
                  ><img
                    src="{{ asset('images/insurers/albuhaira.png') }}"
                    alt="Al Buhaira National Insurance"
                    data-pid="albuhaira"
                    onerror="
                      DELTA.util && DELTA.util.logoFallback(this)
                    " /></span
                ><span class="nm">Al Buhaira National</span></span
              >
            </div>
          </div>
        </div>
      </section>

      <!-- SERVICES -->
      <section class="sec">
        <div class="container-x">
          <div class="sec-head">
            <div>
              <span class="eyebrow">Insurance services</span>
              <h2 class="h-1">Insurance solutions<br />for your needs</h2>
            </div>
            <p class="lead">
              One broker for your personal insurance. Buy motor insurance
              online, or let an advisor compare medical, home, travel, boat and
              pet cover for you.
            </p>
          </div>
          <div class="svc-grid">
            <a class="svc feat rv" href="{{ route('products.motor') }}">
              <div class="body">
                <span
                  class="d-badge d-badge--online"
                  style="align-self: flex-start; margin-bottom: 22px"
                  >Available online</span
                >
                <h3>Motor Insurance</h3>
                <p>
                  Protect your vehicle with flexible insurance options. Compare
                  offers from leading UAE insurers side by side and buy your
                  policy online.
                </p>
                <div class="steps">
                  <span><i class="fas fa-check"></i>Instant quotes</span
                  ><span><i class="fas fa-check"></i>3 insurers compared</span
                  ><span><i class="fas fa-check"></i>Policy issued online</span>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                  <span class="btn-x btn-mint"
                    ><span>Get a Motor Quote</span
                    ><span class="ar"><i class="far fa-arrow-right"></i></span
                  ></span>
                </div>
              </div>
              <div class="art"><img src="{{ asset('images/cut/car.webp') }}" alt="Car" /></div>
            </a>
            <a class="svc rv" href="{{ route('products.medical') }}">
              <div class="art">
                <img
                  src="{{ asset('images/cut/family.webp') }}"
                  alt="Medical Insurance"
                  onerror="
                    this.outerHTML =
                      '<i class=&quot;fal fa-heart-pulse big-ic&quot;></i>'
                  "
                />
              </div>
              <div class="body">
                <h3>Medical Insurance</h3>
                <p>
                  Health cover designed around your needs — for individuals,
                  families and employees.
                </p>
                <div class="foot">
                  <span class="d-badge d-badge--advisor"
                    >Quote by an advisor</span
                  ><span class="arr"><i class="far fa-arrow-right"></i></span>
                </div>
              </div>
            </a>
            <a class="svc rv" href="{{ route('products.home') }}">
              <div class="art">
                <img
                  src="{{ asset('images/cut/villa.webp') }}"
                  alt="Home Insurance"
                  onerror="
                    this.outerHTML =
                      '<i class=&quot;fal fa-house-heart big-ic&quot;></i>'
                  "
                />
              </div>
              <div class="body">
                <h3>Home Insurance</h3>
                <p>
                  Protect your home and valuable belongings against the
                  unexpected.
                </p>
                <div class="foot">
                  <span class="d-badge d-badge--advisor"
                    >Quote by an advisor</span
                  ><span class="arr"><i class="far fa-arrow-right"></i></span>
                </div>
              </div>
            </a>
            <a class="svc rv" href="{{ route('products.travel') }}">
              <div class="art">
                <img
                  src="{{ asset('images/cut/travel.webp') }}"
                  alt="Travel Insurance"
                  onerror="
                    this.outerHTML =
                      '<i class=&quot;fal fa-plane-departure big-ic&quot;></i>'
                  "
                />
              </div>
              <div class="body">
                <h3>Travel Insurance</h3>
                <p>
                  Medical and trip protection for every journey, with visa-ready
                  options.
                </p>
                <div class="foot">
                  <span class="d-badge d-badge--advisor"
                    >Quote by an advisor</span
                  ><span class="arr"><i class="far fa-arrow-right"></i></span>
                </div>
              </div>
            </a>
            <a class="svc rv" href="{{ route('products.boat') }}">
              <div class="art photo">
                <img
                  src="{{ asset('images/photo/boat.jpg') }}"
                  alt="Boat Insurance"
                  onerror="
                    this.outerHTML =
                      '<i class=&quot;fal fa-sailboat big-ic&quot;></i>'
                  "
                />
              </div>
              <div class="body">
                <h3>Boat Insurance</h3>
                <p>
                  Insurance solutions for your boat, yacht or jet ski in UAE
                  waters.
                </p>
                <div class="foot">
                  <span class="d-badge d-badge--advisor"
                    >Quote by an advisor</span
                  ><span class="arr"><i class="far fa-arrow-right"></i></span>
                </div>
              </div>
            </a>
            <a class="svc rv" href="{{ route('products.pet') }}">
              <div class="art photo">
                <img
                  src="{{ asset('images/photo/pet.jpg') }}"
                  alt="Pet Insurance"
                  onerror="
                    this.outerHTML =
                      '<i class=&quot;fal fa-paw big-ic&quot;></i>'
                  "
                />
              </div>
              <div class="body">
                <h3>Pet Insurance</h3>
                <p>
                  Coverage options that help you care for your cats and dogs.
                </p>
                <div class="foot">
                  <span class="d-badge d-badge--advisor"
                    >Quote by an advisor</span
                  ><span class="arr"><i class="far fa-arrow-right"></i></span>
                </div>
              </div>
            </a>
            <div class="svc-cta rv">
              <img
                class="mk"
                src="{{ asset('images/brand/delta-glyph.svg') }}"
                alt=""
                aria-hidden="true"
              />
              <span class="eyebrow">Not sure what you need?</span>
              <h3>Talk to an advisor about the right cover for you.</h3>
              <p>
                We will review your situation, compare suitable plans from our
                insurance partners and explain the differences — at no extra
                cost.
              </p>
              <div class="d-flex gap-2 flex-wrap mt-10">
                <a class="btn-x btn-navy" href="{{ route('contact') }}"
                  ><span>Speak to an advisor</span
                  ><span class="ar"><i class="far fa-arrow-right"></i></span></a
                ><a class="btn-x btn-line no-ar" href="tel:+97165747420"
                  ><span>+971 6 574 7420</span></a
                >
              </div>
            </div>
          </div>
        </div>
      </section>

      <div class="marq" aria-hidden="true">
        <div class="track">
          <span>Motor</span><span class="o">Medical</span><span>Home</span
          ><span class="o">Travel</span><span>Boat</span
          ><span class="o">Pet</span><span>Motor</span
          ><span class="o">Medical</span><span>Home</span
          ><span class="o">Travel</span><span>Boat</span
          ><span class="o">Pet</span><span>Motor</span
          ><span class="o">Medical</span><span>Home</span
          ><span class="o">Travel</span><span>Boat</span
          ><span class="o">Pet</span><span>Motor</span
          ><span class="o">Medical</span><span>Home</span
          ><span class="o">Travel</span><span>Boat</span
          ><span class="o">Pet</span>
        </div>
      </div>

      <!-- MOTOR ONLINE -->
      <section class="sec bg-soft">
        <div class="container-x">
          <div class="split">
            <div>
              <span class="eyebrow">Motor insurance, online</span>
              <h2 class="h-1">
                Enter your details once.<br />Compare every offer.
              </h2>
              <p class="lead mt-20">
                We send your request to our connected insurers at the same time,
                then show you price, cover, repair type and extras side by side
                — so the differences are easy to see before you buy.
              </p>
              <div class="d-flex gap-2 flex-wrap mt-30">
                <a class="btn-x btn-navy" href="{{ url(config('site.links.motor_quote')) }}"
                  ><span>Get a Motor Quote</span
                  ><span class="ar"><i class="far fa-arrow-right"></i></span></a
                ><a class="btn-x btn-line no-ar" href="{{ route('products.motor') }}"
                  ><span>How it works</span></a
                >
              </div>
            </div>
            <div class="mock rv">
              <div class="hd">
                <b>2024 Toyota Land Cruiser · Comprehensive</b
                ><span class="d-badge d-badge--advisor">3 insurers</span>
              </div>
              <div class="row-o best">
                <span class="ins-logo" title="Orient Insurance"
                  ><img
                    src="{{ asset('images/insurers/orient.png') }}"
                    alt="Orient Insurance"
                    data-pid="orient"
                    onerror="DELTA.util && DELTA.util.logoFallback(this)"
                /></span>
                <div>
                  <b>Gold Comprehensive</b
                  ><small>Orient · Agency repair · Deductible AED 750</small>
                </div>
                <div class="p">AED 6,812<small>incl. VAT</small></div>
              </div>
              <div class="row-o">
                <span class="ins-logo" title="Sukoon Insurance"
                  ><img
                    src="{{ asset('images/insurers/sukoon.png') }}"
                    alt="Sukoon Insurance"
                    data-pid="sukoon"
                    onerror="DELTA.util && DELTA.util.logoFallback(this)"
                /></span>
                <div>
                  <b>Motor Comprehensive Plus</b
                  ><small>Sukoon · Agency repair · Rent-a-car</small>
                </div>
                <div class="p">AED 7,164<small>incl. VAT</small></div>
              </div>
              <div class="row-o">
                <span class="ins-logo" title="ADNIC"
                  ><img
                    src="{{ asset('images/insurers/adnic.png') }}"
                    alt="ADNIC"
                    data-pid="adnic"
                    onerror="DELTA.util && DELTA.util.logoFallback(this)"
                /></span>
                <div>
                  <b>Premier Motor</b
                  ><small>ADNIC · Agency repair · GCC cover</small>
                </div>
                <div class="p">AED 7,590<small>incl. VAT</small></div>
              </div>
              <small class="d-muted-2"
                >Illustrative example. Your price depends on your vehicle and
                driving history.</small
              >
            </div>
          </div>
          <div class="steps-x mt-60 rv">
            <div>
              <span class="n">01</span><i class="fal fa-user"></i>
              <h4>Your details</h4>
              <p>Age, contact and driving history.</p>
            </div>
            <div>
              <span class="n">02</span><i class="fal fa-car"></i>
              <h4>Your vehicle</h4>
              <p>Make, model, year and value.</p>
            </div>
            <div>
              <span class="n">03</span><i class="fal fa-scale-balanced"></i>
              <h4>Compare offers</h4>
              <p>Price, deductible, repair and extras.</p>
            </div>
            <div>
              <span class="n">04</span><i class="fal fa-file-arrow-up"></i>
              <h4>Upload &amp; pay</h4>
              <p>Emirates ID, licence, Mulkiya.</p>
            </div>
            <div>
              <span class="n">05</span><i class="fal fa-file-certificate"></i>
              <h4>Get your policy</h4>
              <p>Issued by the insurer, sent to you.</p>
            </div>
          </div>
        </div>
      </section>

      <!-- WHY DELTA -->
      <section class="sec">
        <div class="container-x">
          <div class="split">
            <div class="photo-stack rv">
              <div class="main">
                <img src="{{ asset('images/photo/team.jpg') }}" alt="Delta team" />
              </div>
              <div class="badge-yr">
                <b>1990</b><span>Serving clients from Sharjah</span>
              </div>
            </div>
            <div>
              <span class="eyebrow">Why Delta</span>
              <h2 class="h-1">
                A broker who works for you — not for one insurer.
              </h2>
              <p class="lead mt-20">
                We compare options across insurers, explain what each policy
                really covers, and stay with you after you buy — for renewals,
                changes and claims.
              </p>
              <div class="feat-list">
                <div>
                  <i class="fal fa-scale-balanced"></i
                  ><span
                    ><h4>Independent advice</h4>
                    <p>
                      Options from several insurers, explained clearly.
                    </p></span
                  >
                </div>
                <div>
                  <i class="fal fa-user-headset"></i
                  ><span
                    ><h4>A real advisor</h4>
                    <p>Talk to a person who knows your policy.</p></span
                  >
                </div>
                <div>
                  <i class="fal fa-hand-holding-heart"></i
                  ><span
                    ><h4>Claims support</h4>
                    <p>We guide you through every step of a claim.</p></span
                  >
                </div>
                <div>
                  <i class="fal fa-shield-check"></i
                  ><span
                    ><h4>Secure online account</h4>
                    <p>Policies and documents in one place.</p></span
                  >
                </div>
              </div>
            </div>
          </div>
          <div class="stats-x mt-80">
            <div class="rv"><b>1990</b><span>Established in Sharjah</span></div>
            <div class="rv"><b>6</b><span>Personal insurance lines</span></div>
            <div class="rv">
              <b>3</b><span>Insurers compared online for motor</span>
            </div>
            <div class="rv">
              <b>24/7</b><span>Online quotes and policy access</span>
            </div>
          </div>
        </div>
      </section>

      <!-- ADVISOR-ASSISTED -->
      <section class="sec" style="padding-top: 0">
        <div class="container-x">
          <div class="advisor rv">
            <div class="txt">
              <span class="eyebrow">Advisor-assisted cover</span>
              <h2 class="h-2">Medical, home, travel, boat or pet insurance?</h2>
              <p class="mt-15">
                Tell us what you need. A Delta advisor compares suitable plans
                and sends you a personal quote — usually within one working day.
              </p>
              <div class="chips">
                <a class="chip" href="{{ route('products.medical') }}#request"
                  ><i class="fal fa-heart-pulse"></i>Medical</a
                ><a class="chip" href="{{ route('products.home') }}#request"
                  ><i class="fal fa-house-heart"></i>Home</a
                ><a class="chip" href="{{ route('products.travel') }}#request"
                  ><i class="fal fa-plane-departure"></i>Travel</a
                ><a class="chip" href="{{ route('products.boat') }}#request"
                  ><i class="fal fa-sailboat"></i>Boat</a
                ><a class="chip" href="{{ route('products.pet') }}#request"
                  ><i class="fal fa-paw"></i>Pet</a
                >
              </div>
              <a class="btn-x btn-navy" href="{{ route('contact') }}"
                ><span>Request a quote</span
                ><span class="ar"><i class="far fa-arrow-right"></i></span
              ></a>
            </div>
            <div
              class="img"
              style="
                background-image: url(&quot;{{ asset('images/photo/family-park.jpg') }}&quot;);
              "
            ></div>
          </div>
        </div>
      </section>

      <!-- FAQ -->
      <section class="sec bg-soft">
        <div class="container-x">
          <div class="row g-5">
            <div class="col-lg-4">
              <span class="eyebrow">FAQ</span>
              <h2 class="h-2">Questions we hear often</h2>
              <p class="mt-15">
                Can’t find your answer? Our team is happy to help.
              </p>
              <a class="btn-x btn-line no-ar" href="{{ route('contact') }}"
                ><span>Contact us</span></a
              >
            </div>
            <div class="col-lg-8 faq-x">
              <details open>
                <summary>Which insurance can I buy online?</summary>
                <p>
                  Motor insurance is fully online — quote, compare, upload
                  documents, pay and receive your policy. For medical, home,
                  travel, boat and pet insurance, send a request and an advisor
                  prepares the right options for you.
                </p>
              </details>
              <details>
                <summary>
                  Why use a broker instead of going to an insurer directly?
                </summary>
                <p>
                  A broker compares options from different insurers and explains
                  the differences in cover, exclusions and price, so you can
                  choose with confidence. We also help with renewals and claims.
                </p>
              </details>
              <details>
                <summary>What documents do I need for motor insurance?</summary>
                <p>
                  Your Emirates ID, UAE driving licence and vehicle registration
                  (Mulkiya). For a new vehicle, the dealer or customs
                  certificate is used instead of the Mulkiya.
                </p>
              </details>
              <details>
                <summary>Is my data secure?</summary>
                <p>
                  Documents are kept in private, encrypted storage and are only
                  shared with the insurer you choose, to issue your policy.
                </p>
              </details>
            </div>
          </div>
        </div>
      </section>

      <!-- CTA -->
      <section class="sec-sm">
        <div class="container-x">
          <div class="cta-x rv">
            <img
              class="mk"
              src="{{ asset('images/brand/delta-glyph-white.svg') }}"
              alt=""
              aria-hidden="true"
            />
            <div>
              <span class="eyebrow on-dark">Get started</span>
              <h2 class="h-1">Need car insurance today?</h2>
              <p class="lead mt-15" style="color: #c2d1de">
                Compare offers online in a few minutes — or call us and we’ll
                help.
              </p>
            </div>
            <div class="d-flex gap-2 flex-wrap justify-content-lg-end">
              <a class="btn-x btn-mint" href="{{ url(config('site.links.motor_quote')) }}"
                ><span>Get a Motor Quote</span
                ><span class="ar"><i class="far fa-arrow-right"></i></span></a
              ><a class="btn-x btn-ghost-dark no-ar" href="tel:+97165747420"
                ><span>+971 6 574 7420</span></a
              >
            </div>
          </div>
        </div>
      </section>
@endsection
