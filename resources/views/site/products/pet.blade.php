@extends('layouts.site', ['active' => 'services'])

@section('title', 'Pet Insurance — Delta Insurance Services')
@section('description', 'Veterinary treatment can be expensive. Pet insurance helps you care for your cats and dogs without worrying about unexpected vet bills.')

@section('content')


        <section class="phero">
          <div class="container-x">
            <div class="phero-grid">
              <div>
                <div class="crumbs"><a href="{{ route('home') }}">Home</a><i class="far fa-chevron-right"></i><a href="{{ route('services') }}">Insurance</a><i class="far fa-chevron-right"></i><span>Pet Insurance</span></div>
                <span class="d-badge d-badge--advisor"><i class="fal fa-user-headset"></i> Quote prepared by an advisor</span>
                <h1 class="h-1 mt-20">Coverage options for your pets</h1>
                <p class="lead mt-20">Veterinary treatment can be expensive. Pet insurance helps you care for your cats and dogs without worrying about unexpected vet bills.</p>
                <div class="ctas"><a class="btn-x btn-navy" href="#request" ><span>Request a quote</span><span class="ar"><i class="far fa-arrow-right"></i></span></a><a class="btn-x btn-line  no-ar" href="tel:+97165747420" ><span>+971 6 574 7420</span></a></div>
              </div>
              <div class="ph "><img src="{{ asset('images/photo/pet.jpg') }}" alt="Pet Insurance" onerror="this.outerHTML='<i class=&quot;fal fa-paw big-ic&quot;></i>'" /></div>
            </div>
          </div>
        </section>
        <section class="sec">
          <div class="container-x">
            <div class="sec-head"><div><span class="eyebrow">What can be covered</span><h2 class="h-2">Coverage overview</h2></div><p class="lead">Cover depends on the insurer and the plan you choose. These are the benefits most plans offer.</p></div>
            <div class="cover-grid"><div><i class="fal fa-shield-check"></i>Accident and illness vet fees</div><div><i class="fal fa-shield-check"></i>Surgery and hospitalisation</div><div><i class="fal fa-shield-check"></i>Diagnostics and medication</div><div><i class="fal fa-shield-check"></i>Third-party liability for dogs (plan dependent)</div><div><i class="fal fa-shield-check"></i>Loss or theft options</div><div><i class="fal fa-shield-check"></i>Optional wellness benefits</div></div>
            <div class="row g-4 mt-40">
              <div class="col-lg-6"><div class="card-x soft h-100"><h3 class="h-3 mb-15">Who is it for?</h3><ul class="check-list"><li><i class="fas fa-check"></i><span>Dog owners</span></li><li><i class="fas fa-check"></i><span>Cat owners</span></li><li><i class="fas fa-check"></i><span>Owners of pets with no serious pre-existing conditions</span></li></ul></div></div>
              <div class="col-lg-6"><div class="card-x navy h-100"><span class="eyebrow on-dark">Buying online</span><h3 class="h-3 mb-15">Online comparison is available for motor insurance</h3><p>For pet insurance, our advisors compare options for you and send a personal quote. Online quotes for more products are coming.</p><a class="btn-x btn-mint sm" href="{{ url(config('site.links.motor_quote')) }}" ><span>Get a motor quote</span><span class="ar"><i class="far fa-arrow-right"></i></span></a></div></div>
            </div>
          </div>
        </section>
        <section class="sec" style="padding-top:0">
          <div class="container-x"><span class="eyebrow">Why Delta</span><h2 class="h-2 mb-40">What you get with us</h2><div class="values three"><div><i class="fal fa-shield-dog"></i><h4>Dogs & cats</h4><p>Plans for different breeds and ages.</p></div><div><i class="fal fa-stethoscope"></i><h4>Vet fees</h4><p>Help with treatment costs.</p></div><div><i class="fal fa-user-headset"></i><h4>Friendly advice</h4><p>We explain limits and exclusions.</p></div></div></div>
        </section>
        <section class="sec bg-soft" id="request">
          <div class="container-x">
            <div class="row g-5">
              <div class="col-lg-5"><span class="eyebrow">FAQ</span><h2 class="h-2 mb-30">Pet Insurance questions</h2><div class="faq-x"><details open><summary>What pets can be insured?</summary><p>Most plans cover cats and dogs. Age limits and breed conditions vary by insurer.</p></details><details ><summary>Are pre-existing conditions covered?</summary><p>Usually not. We will explain how each insurer treats existing conditions.</p></details><details ><summary>Is there a waiting period?</summary><p>Many policies have a short waiting period for illness cover. We will point this out before you buy.</p></details></div></div>
              <div class="col-lg-7">
            <form class="d-card d-card--glow" data-lead-form data-product="pet" novalidate style="padding:36px">
              <h3 class="d-h3 mb-10">Request a quote</h3>
              <p class="d-muted-2 mb-25">Share a few details and a Delta advisor will prepare options for you.</p>
              <div class="row g-3">
                <div class="col-md-6"><label class="d-field"><span>Full name <em>*</em></span><input class="d-input" name="name" required /></label></div>
                <div class="col-md-6"><label class="d-field"><span>Mobile <em>*</em></span><div class="d-input-group"><span class="pre">+971</span><input class="d-input" name="mobile" required placeholder="50 123 4567" /></div></label></div>
                <div class="col-md-6"><label class="d-field"><span>Email <em>*</em></span><input class="d-input" type="email" name="email" required /></label></div>
                <div class="col-md-6"><label class="d-field"><span>Insurance type</span><select class="d-select" name="product"><option value="medical" >Medical Insurance</option><option value="home" >Home Insurance</option><option value="travel" >Travel Insurance</option><option value="boat" >Boat Insurance</option><option value="pet" selected>Pet Insurance</option></select></label></div>
                <div class="col-12"><label class="d-field"><span>What do you need?</span><textarea class="d-textarea" name="message" placeholder="Type of pet, breed, age and any medical history"></textarea></label></div>
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
