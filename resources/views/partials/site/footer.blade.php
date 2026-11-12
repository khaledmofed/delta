    <footer class="ftr">
      <div class="container-x">
        <div class="top">
          <div>
            <img class="logo" src="{{ asset('images/brand/delta-logo-horizontal-white.svg') }}" alt="Delta Insurance Services L.L.C" />
            <p style="max-width:340px">Your trusted insurance broker in Sharjah, UAE. Since 1990 we have helped individuals, families and businesses choose the right cover.</p>
            <div class="nl"><input type="email" placeholder="Email for renewal reminders" aria-label="Email" /><button type="button" onclick="DELTA.ui.toast('Subscribed (demo)','ok')">Subscribe</button></div>
          </div>
          <div><h5>Insurance</h5><ul>@foreach (config('site.services') as $key => $s)<li><a href="{{ route('products.'.$key) }}">{{ $s['name'] }}</a></li>@endforeach</ul></div>
          <div><h5>Company</h5><ul><li><a href="{{ route('about') }}">About Us</a></li><li><a href="{{ route('services') }}">All services</a></li><li><a href="{{ url(config('site.links.motor_quote')) }}">Compare motor quotes</a></li><li><a href="{{ route('claims') }}">Claims &amp; Support</a></li><li><a href="{{ route('track') }}">Track my order</a></li><li><a href="{{ route('contact') }}">Contact</a></li></ul></div>
          <div><h5>Get in touch</h5><ul>
            <li><a href="tel:{{ config('site.phone_href') }}"><i class="fal fa-phone"></i>{{ config('site.phone') }}</a></li>
            <li><a href="mailto:{{ config('site.email') }}"><i class="fal fa-envelope"></i>{{ config('site.email') }}</a></li>
            <li><i class="fal fa-location-dot"></i>{{ config('site.location') }}</li></ul>
            <div class="mt-20"><a class="btn-x btn-mint sm" href="{{ route('contact') }}" ><span>Speak to an Advisor</span><span class="ar"><i class="far fa-arrow-right"></i></span></a></div>
          </div>
        </div>
        <div class="bottom"><span>© <span data-year>{{ date('Y') }}</span> Delta Insurance Services L.L.C. All rights reserved.</span><ul><li><a href="#!">Terms</a></li><li><a href="#!">Privacy</a></li><li><a href="#!">Complaints</a></li></ul></div>
        <div class="big" aria-hidden="true"><span class="w">DELTA</span><span class="stack"><span>INSURANCE</span><span>SERVICES</span><span>L.L.C</span></span></div>
      </div>
    </footer>
    <a href="#" class="to-top" aria-label="Back to top"><i class="far fa-arrow-up"></i></a>
