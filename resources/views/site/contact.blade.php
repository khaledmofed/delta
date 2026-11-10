@extends('layouts.site', ['active' => 'contact'])

@section('title', 'Contact Us — Delta Insurance Services')
@section('description', 'Contact Delta Insurance Services in Sharjah: +971 6 574 7420, dis@deltains-brokers.com')

@section('content')


        <section class="phero">
          <div class="container-x">
            <div class="phero-grid">
              <div>
                <div class="crumbs"><a href="{{ route('home') }}">Home</a><i class="far fa-chevron-right"></i><span>Contact</span></div>
                <span class="eyebrow">Contact us</span>
                <h1 class="h-1">Speak to an advisor</h1>
                <p class="lead mt-20">Questions about a policy, a quote or a claim? Our team in Sharjah is here to help.</p>
              </div>
              
            </div>
          </div>
        </section>
        <section class="sec" style="padding-top:60px">
          <div class="container-x">
            <div class="row g-4">
              <div class="col-lg-5">
                <div class="card-x navy h-100" style="padding:40px">
                  <h3 class="h-3 mb-20">Delta Insurance Services L.L.C</h3>
                  <ul class="check-list" style="color:#c9d6e2">
                    <li><i class="fas fa-phone" style="background:rgba(0,255,151,.12);color:var(--mint)"></i><span><b style="color:#fff">Phone</b><br/><a href="tel:+97165747420">+971 6 574 7420</a></span></li>
                    <li><i class="fas fa-envelope" style="background:rgba(0,255,151,.12);color:var(--mint)"></i><span><b style="color:#fff">Email</b><br/><a href="mailto:dis@deltains-brokers.com">dis@deltains-brokers.com</a></span></li>
                    <li><i class="fas fa-location-dot" style="background:rgba(0,255,151,.12);color:var(--mint)"></i><span><b style="color:#fff">Office</b><br/>Sharjah, United Arab Emirates</span></li>
                    <li><i class="fas fa-clock" style="background:rgba(0,255,151,.12);color:var(--mint)"></i><span><b style="color:#fff">Online</b><br/>Motor quotes and your account are available 24/7.</span></li>
                  </ul>
                  <div class="mt-30"><a class="btn-x btn-mint sm" href="{{ url(config('site.links.motor_quote')) }}" ><span>Get a motor quote</span><span class="ar"><i class="far fa-arrow-right"></i></span></a></div>
                </div>
              </div>
              <div class="col-lg-7">
                <form class="d-card d-card--glow" data-lead-form novalidate style="padding:40px">
                  <h3 class="d-h3 mb-10">Send us a message</h3>
                  <p class="d-muted-2 mb-25">We usually reply within one working day.</p>
                  <div class="row g-3">
                    <div class="col-md-6"><label class="d-field"><span>Full name <em>*</em></span><input class="d-input" name="name" required /></label></div>
                    <div class="col-md-6"><label class="d-field"><span>Mobile <em>*</em></span><div class="d-input-group"><span class="pre">+971</span><input class="d-input" name="mobile" required /></div></label></div>
                    <div class="col-md-6"><label class="d-field"><span>Email <em>*</em></span><input class="d-input" type="email" name="email" required /></label></div>
                    <div class="col-md-6"><label class="d-field"><span>Topic</span><select class="d-select" name="product"><option value="motor">Motor Insurance</option><option value="medical">Medical Insurance</option><option value="home">Home Insurance</option><option value="travel">Travel Insurance</option><option value="boat">Boat Insurance</option><option value="pet">Pet Insurance</option><option value="general">General enquiry</option></select></label></div>
                    <div class="col-12"><label class="d-field"><span>Message <em>*</em></span><textarea class="d-textarea" name="message" required></textarea></label></div>
                    <div class="col-12"><label class="d-check"><input type="checkbox" name="consent" required /> I agree to be contacted by Delta Insurance about this enquiry.</label></div>
                    <div class="col-12"><button class="btn-x btn-navy" type="submit"><span>Send message</span><span class="ar"><i class="far fa-arrow-right"></i></span></button></div>
                  </div>
                </form>
              </div>
            </div>
          </div>
        </section>
@endsection
