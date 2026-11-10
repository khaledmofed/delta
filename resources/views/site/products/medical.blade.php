@extends('layouts.site', ['active' => 'services'])

@section('title', 'Medical Insurance — Delta Insurance Services')
@section('description', 'Whether you need cover for yourself, your family or your employees, Delta helps you compare medical insurance plans and choose a network, benefits and budget that suit you.')

@section('content')


        <section class="phero">
          <div class="container-x">
            <div class="phero-grid">
              <div>
                <div class="crumbs"><a href="{{ route('home') }}">Home</a><i class="far fa-chevron-right"></i><a href="{{ route('services') }}">Insurance</a><i class="far fa-chevron-right"></i><span>Medical Insurance</span></div>
                <span class="d-badge d-badge--advisor"><i class="fal fa-user-headset"></i> Quote prepared by an advisor</span>
                <h1 class="h-1 mt-20">Medical insurance built around your healthcare needs</h1>
                <p class="lead mt-20">Whether you need cover for yourself, your family or your employees, Delta helps you compare medical insurance plans and choose a network, benefits and budget that suit you.</p>
                <div class="ctas"><a class="btn-x btn-navy" href="#request" ><span>Request a quote</span><span class="ar"><i class="far fa-arrow-right"></i></span></a><a class="btn-x btn-line  no-ar" href="tel:+97165747420" ><span>+971 6 574 7420</span></a></div>
              </div>
              <div class="ph "><img src="{{ asset('images/photo/medical-doctor.jpg') }}" alt="Medical Insurance" onerror="this.outerHTML='<i class=&quot;fal fa-heart-pulse big-ic&quot;></i>'" /></div>
            </div>
          </div>
        </section>
        <section class="sec">
          <div class="container-x">
            <div class="sec-head"><div><span class="eyebrow">What can be covered</span><h2 class="h-2">Coverage overview</h2></div><p class="lead">Cover depends on the insurer and the plan you choose. These are the benefits most plans offer.</p></div>
            <div class="cover-grid"><div><i class="fal fa-shield-check"></i>Inpatient and outpatient treatment</div><div><i class="fal fa-shield-check"></i>Hospital networks — basic to extended</div><div><i class="fal fa-shield-check"></i>Maternity benefits (plan dependent)</div><div><i class="fal fa-shield-check"></i>Pharmacy and prescribed medication</div><div><i class="fal fa-shield-check"></i>Dental and optical options</div><div><i class="fal fa-shield-check"></i>Emergency treatment</div></div>
            <div class="row g-4 mt-40">
              <div class="col-lg-6"><div class="card-x soft h-100"><h3 class="h-3 mb-15">Who is it for?</h3><ul class="check-list"><li><i class="fas fa-check"></i><span>Individuals and families living in the UAE</span></li><li><i class="fas fa-check"></i><span>Parents and domestic workers (where the plan allows)</span></li><li><i class="fas fa-check"></i><span>Small and medium businesses providing employee cover</span></li></ul></div></div>
              <div class="col-lg-6"><div class="card-x navy h-100"><span class="eyebrow on-dark">Buying online</span><h3 class="h-3 mb-15">Online comparison is available for motor insurance</h3><p>For medical insurance, our advisors compare options for you and send a personal quote. Online quotes for more products are coming.</p><a class="btn-x btn-mint sm" href="{{ url(config('site.links.motor_quote')) }}" ><span>Get a motor quote</span><span class="ar"><i class="far fa-arrow-right"></i></span></a></div></div>
            </div>
          </div>
        </section>
        <section class="sec" style="padding-top:0">
          <div class="container-x"><span class="eyebrow">Why Delta</span><h2 class="h-2 mb-40">What you get with us</h2><div class="values three"><div><i class="fal fa-scale-balanced"></i><h4>Compare plans</h4><p>We explain networks, co-pays and limits across insurers.</p></div><div><i class="fal fa-people-group"></i><h4>Family & group</h4><p>Cover for families and employee groups.</p></div><div><i class="fal fa-user-headset"></i><h4>Renewal support</h4><p>We review your cover each year before renewal.</p></div></div></div>
        </section>
        <section class="sec bg-soft" id="request">
          <div class="container-x">
            <div class="row g-5">
              <div class="col-lg-5"><span class="eyebrow">FAQ</span><h2 class="h-2 mb-30">Medical Insurance questions</h2><div class="faq-x"><details open><summary>Is health insurance mandatory in the UAE?</summary><p>Health insurance requirements depend on the emirate you live and work in. Our advisors can explain what applies to you and your family.</p></details><details ><summary>Can I add my family to my plan?</summary><p>Most plans allow dependants to be added. We will show you the options and costs for each member.</p></details><details ><summary>How long does it take to get a quote?</summary><p>Once we have the member details we usually come back with options quickly. Complex group schemes may take longer.</p></details></div></div>
              <div class="col-lg-7">
            <form class="d-card d-card--glow" data-lead-form method="post" action="{{ route('leads.store') }}" data-product="medical" novalidate style="padding:36px">
              @include('partials.site.lead-fields', ['source' => 'product'])
              <h3 class="d-h3 mb-10">Request a quote</h3>
              <p class="d-muted-2 mb-25">Share a few details and a Delta advisor will prepare options for you.</p>
              <div class="row g-3">
                <div class="col-md-6"><label class="d-field"><span>Full name <em>*</em></span><input class="d-input" name="name" required /></label></div>
                <div class="col-md-6"><label class="d-field"><span>Mobile <em>*</em></span><div class="d-input-group"><span class="pre">+971</span><input class="d-input" name="mobile" required placeholder="50 123 4567" /></div></label></div>
                <div class="col-md-6"><label class="d-field"><span>Email <em>*</em></span><input class="d-input" type="email" name="email" required /></label></div>
                <div class="col-md-6"><label class="d-field"><span>Insurance type</span><select class="d-select" name="product"><option value="medical" selected>Medical Insurance</option><option value="home" >Home Insurance</option><option value="travel" >Travel Insurance</option><option value="boat" >Boat Insurance</option><option value="pet" >Pet Insurance</option></select></label></div>
                <div class="col-12"><label class="d-field"><span>What do you need?</span><textarea class="d-textarea" name="message" placeholder="Number of people to insure, ages and preferred network (if any)"></textarea></label></div>
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
