@extends('layouts.site', ['active' => 'services'])

@section('title', 'Boat Insurance — Delta Insurance Services')
@section('description', 'Enjoy your time on the water with cover for your boat, yacht or jet ski. We help you find a policy that fits how and where you use your vessel.')

@section('content')


        <section class="phero">
          <div class="container-x">
            <div class="phero-grid">
              <div>
                <div class="crumbs"><a href="{{ route('home') }}">Home</a><i class="far fa-chevron-right"></i><a href="{{ route('services') }}">Insurance</a><i class="far fa-chevron-right"></i><span>Boat Insurance</span></div>
                <span class="d-badge d-badge--advisor"><i class="fal fa-user-headset"></i> Quote prepared by an advisor</span>
                <h1 class="h-1 mt-20">Insurance solutions for your marine assets</h1>
                <p class="lead mt-20">Enjoy your time on the water with cover for your boat, yacht or jet ski. We help you find a policy that fits how and where you use your vessel.</p>
                <div class="ctas"><a class="btn-x btn-navy" href="#request" ><span>Request a quote</span><span class="ar"><i class="far fa-arrow-right"></i></span></a><a class="btn-x btn-line  no-ar" href="tel:+97165747420" ><span>+971 6 574 7420</span></a></div>
              </div>
              <div class="ph "><img src="{{ asset('images/photo/boat.jpg') }}" alt="Boat Insurance" onerror="this.outerHTML='<i class=&quot;fal fa-sailboat big-ic&quot;></i>'" /></div>
            </div>
          </div>
        </section>
        <section class="sec">
          <div class="container-x">
            <div class="sec-head"><div><span class="eyebrow">What can be covered</span><h2 class="h-2">Coverage overview</h2></div><p class="lead">Cover depends on the insurer and the plan you choose. These are the benefits most plans offer.</p></div>
            <div class="cover-grid"><div><i class="fal fa-shield-check"></i>Hull and machinery damage</div><div><i class="fal fa-shield-check"></i>Third-party liability</div><div><i class="fal fa-shield-check"></i>Theft</div><div><i class="fal fa-shield-check"></i>Salvage and wreck removal</div><div><i class="fal fa-shield-check"></i>Personal accident options</div><div><i class="fal fa-shield-check"></i>Navigation limits to suit your use</div></div>
            <div class="row g-4 mt-40">
              <div class="col-lg-6"><div class="card-x soft h-100"><h3 class="h-3 mb-15">Who is it for?</h3><ul class="check-list"><li><i class="fas fa-check"></i><span>Leisure boat and yacht owners</span></li><li><i class="fas fa-check"></i><span>Jet ski owners</span></li><li><i class="fas fa-check"></i><span>Owners who keep vessels in UAE marinas</span></li></ul></div></div>
              <div class="col-lg-6"><div class="card-x navy h-100"><span class="eyebrow on-dark">Buying online</span><h3 class="h-3 mb-15">Online comparison is available for motor insurance</h3><p>For boat insurance, our advisors compare options for you and send a personal quote. Online quotes for more products are coming.</p><a class="btn-x btn-mint sm" href="{{ url(config('site.links.motor_quote')) }}" ><span>Get a motor quote</span><span class="ar"><i class="far fa-arrow-right"></i></span></a></div></div>
            </div>
          </div>
        </section>
        <section class="sec" style="padding-top:0">
          <div class="container-x"><span class="eyebrow">Why Delta</span><h2 class="h-2 mb-40">What you get with us</h2><div class="values three"><div><i class="fal fa-anchor"></i><h4>Tailored cover</h4><p>Based on vessel type, value and use.</p></div><div><i class="fal fa-compass"></i><h4>Navigation limits</h4><p>Cover for UAE waters and beyond.</p></div><div><i class="fal fa-user-headset"></i><h4>Specialist advice</h4><p>We explain marine terms clearly.</p></div></div></div>
        </section>
        <section class="sec bg-soft" id="request">
          <div class="container-x">
            <div class="row g-5">
              <div class="col-lg-5"><span class="eyebrow">FAQ</span><h2 class="h-2 mb-30">Boat Insurance questions</h2><div class="faq-x"><details open><summary>What information do I need for a boat quote?</summary><p>Vessel type, length, year, engine details, value, mooring location and your boating experience.</p></details><details ><summary>Does boat insurance cover jet skis?</summary><p>Many insurers offer cover for personal watercraft. We will find the right option for you.</p></details><details ><summary>Is third-party liability included?</summary><p>Most boat policies include liability cover. Limits vary, so we will compare them for you.</p></details></div></div>
              <div class="col-lg-7">
            <form class="d-card d-card--glow" data-lead-form method="post" action="{{ route('leads.store') }}" data-product="boat" novalidate style="padding:36px">
              @include('partials.site.lead-fields', ['source' => 'product'])
              <h3 class="d-h3 mb-10">Request a quote</h3>
              <p class="d-muted-2 mb-25">Share a few details and a Delta advisor will prepare options for you.</p>
              <div class="row g-3">
                <div class="col-md-6"><label class="d-field"><span>Full name <em>*</em></span><input class="d-input" name="name" required /></label></div>
                <div class="col-md-6"><label class="d-field"><span>Mobile <em>*</em></span><div class="d-input-group"><span class="pre">+971</span><input class="d-input" name="mobile" required placeholder="50 123 4567" /></div></label></div>
                <div class="col-md-6"><label class="d-field"><span>Email <em>*</em></span><input class="d-input" type="email" name="email" required /></label></div>
                <div class="col-md-6"><label class="d-field"><span>Insurance type</span><select class="d-select" name="product"><option value="medical" >Medical Insurance</option><option value="home" >Home Insurance</option><option value="travel" >Travel Insurance</option><option value="boat" selected>Boat Insurance</option><option value="pet" >Pet Insurance</option></select></label></div>
                <div class="col-12"><label class="d-field"><span>What do you need?</span><textarea class="d-textarea" name="message" placeholder="Vessel type, length, year, value and where it is moored"></textarea></label></div>
                <div class="col-md-6"><label class="d-field"><span>Preferred contact</span><select class="d-select" name="contactPref"><option>Phone call</option><option>WhatsApp</option><option>Email</option></select></label></div>
                <div class="col-md-6"><label class="d-field"><span>Emirate</span><select class="d-select" name="emirate"><option>Sharjah</option><option>Dubai</option><option>Abu Dhabi</option><option>Ajman</option><option>Umm Al Quwain</option><option>Ras Al Khaimah</option><option>Fujairah</option></select></label></div>
                <div class="col-12"><label class="d-check"><input type="checkbox" name="consent" required /> I agree to be contacted by Delta Insurance about this request.</label></div>
                <div class="col-12"><button class="btn-x btn-navy" type="submit"><span>Send request</span><span class="ar"><i class="far fa-arrow-right"></i></span></button></div>
              </div>
            </form></div>
            </div>
          </div>
        </section>
@endsection
