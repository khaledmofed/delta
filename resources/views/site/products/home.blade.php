@extends('layouts.site', ['active' => 'services'])

@section('title', 'Home Insurance — Delta Insurance Services')
@section('description', 'From apartments to villas, owners to tenants — home insurance helps protect your building, contents and valuables against unexpected events.')

@section('content')


        <section class="phero">
          <div class="container-x">
            <div class="phero-grid">
              <div>
                <div class="crumbs"><a href="{{ route('home') }}">Home</a><i class="far fa-chevron-right"></i><a href="{{ route('services') }}">Insurance</a><i class="far fa-chevron-right"></i><span>Home Insurance</span></div>
                <span class="d-badge d-badge--advisor"><i class="fal fa-user-headset"></i> Quote prepared by an advisor</span>
                <h1 class="h-1 mt-20">Protect your home and everything in it</h1>
                <p class="lead mt-20">From apartments to villas, owners to tenants — home insurance helps protect your building, contents and valuables against unexpected events.</p>
                <div class="ctas"><a class="btn-x btn-navy" href="#request" ><span>Request a quote</span><span class="ar"><i class="far fa-arrow-right"></i></span></a><a class="btn-x btn-line  no-ar" href="tel:+97165747420" ><span>+971 6 574 7420</span></a></div>
              </div>
              <div class="ph cut"><img src="{{ asset('images/cut/villa.webp') }}" alt="Home Insurance" onerror="this.outerHTML='<i class=&quot;fal fa-house-heart big-ic&quot;></i>'" /></div>
            </div>
          </div>
        </section>
        <section class="sec">
          <div class="container-x">
            <div class="sec-head"><div><span class="eyebrow">What can be covered</span><h2 class="h-2">Coverage overview</h2></div><p class="lead">Cover depends on the insurer and the plan you choose. These are the benefits most plans offer.</p></div>
            <div class="cover-grid"><div><i class="fal fa-shield-check"></i>Building structure (for owners)</div><div><i class="fal fa-shield-check"></i>Home contents and furniture</div><div><i class="fal fa-shield-check"></i>Personal belongings and valuables</div><div><i class="fal fa-shield-check"></i>Accidental damage options</div><div><i class="fal fa-shield-check"></i>Temporary accommodation if your home is unliveable</div><div><i class="fal fa-shield-check"></i>Personal liability</div></div>
            <div class="row g-4 mt-40">
              <div class="col-lg-6"><div class="card-x soft h-100"><h3 class="h-3 mb-15">Who is it for?</h3><ul class="check-list"><li><i class="fas fa-check"></i><span>Home owners (building and contents)</span></li><li><i class="fas fa-check"></i><span>Tenants (contents and personal belongings)</span></li><li><i class="fas fa-check"></i><span>Landlords with rented properties</span></li></ul></div></div>
              <div class="col-lg-6"><div class="card-x navy h-100"><span class="eyebrow on-dark">Buying online</span><h3 class="h-3 mb-15">Online comparison is available for motor insurance</h3><p>For home insurance, our advisors compare options for you and send a personal quote. Online quotes for more products are coming.</p><a class="btn-x btn-mint sm" href="{{ url(config('site.links.motor_quote')) }}" ><span>Get a motor quote</span><span class="ar"><i class="far fa-arrow-right"></i></span></a></div></div>
            </div>
          </div>
        </section>
        <section class="sec" style="padding-top:0">
          <div class="container-x"><span class="eyebrow">Why Delta</span><h2 class="h-2 mb-40">What you get with us</h2><div class="values three"><div><i class="fal fa-house-circle-check"></i><h4>Owners & tenants</h4><p>Cover for building, contents or both.</p></div><div><i class="fal fa-gem"></i><h4>Valuables</h4><p>Options for jewellery, electronics and more.</p></div><div><i class="fal fa-hand-holding-heart"></i><h4>Claims support</h4><p>We help you through the claim process.</p></div></div></div>
        </section>
        <section class="sec bg-soft" id="request">
          <div class="container-x">
            <div class="row g-5">
              <div class="col-lg-5"><span class="eyebrow">FAQ</span><h2 class="h-2 mb-30">Home Insurance questions</h2><div class="faq-x"><details open><summary>I rent my apartment — do I need home insurance?</summary><p>Your landlord usually insures the building, but not your belongings. Contents insurance protects your furniture, electronics and personal items.</p></details><details ><summary>How do I value my contents?</summary><p>Our advisors can help you estimate the replacement value of your contents so you are not under-insured.</p></details><details ><summary>Are valuables covered automatically?</summary><p>High-value items often need to be listed separately. We will explain the limits of each plan.</p></details></div></div>
              <div class="col-lg-7">
            <form class="d-card d-card--glow" data-lead-form data-product="home" novalidate style="padding:36px">
              <h3 class="d-h3 mb-10">Request a quote</h3>
              <p class="d-muted-2 mb-25">Share a few details and a Delta advisor will prepare options for you.</p>
              <div class="row g-3">
                <div class="col-md-6"><label class="d-field"><span>Full name <em>*</em></span><input class="d-input" name="name" required /></label></div>
                <div class="col-md-6"><label class="d-field"><span>Mobile <em>*</em></span><div class="d-input-group"><span class="pre">+971</span><input class="d-input" name="mobile" required placeholder="50 123 4567" /></div></label></div>
                <div class="col-md-6"><label class="d-field"><span>Email <em>*</em></span><input class="d-input" type="email" name="email" required /></label></div>
                <div class="col-md-6"><label class="d-field"><span>Insurance type</span><select class="d-select" name="product"><option value="medical" >Medical Insurance</option><option value="home" selected>Home Insurance</option><option value="travel" >Travel Insurance</option><option value="boat" >Boat Insurance</option><option value="pet" >Pet Insurance</option></select></label></div>
                <div class="col-12"><label class="d-field"><span>What do you need?</span><textarea class="d-textarea" name="message" placeholder="Property type, location, owner/tenant, estimated contents value"></textarea></label></div>
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
