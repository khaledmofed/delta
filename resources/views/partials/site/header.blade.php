    <div class="topbar">
      <div class="container-x">
        <div class="l"><span><i class="fal fa-location-dot"></i>{{ config('site.location') }}</span><span class="hide-sm"><i class="fal fa-clock"></i>Insurance brokers since 1990</span></div>
        <div class="r"><a href="tel:{{ config('site.phone_href') }}"><i class="fal fa-phone"></i>{{ config('site.phone') }}</a><a class="hide-sm" href="mailto:{{ config('site.email') }}"><i class="fal fa-envelope"></i>{{ config('site.email') }}</a></div>
      </div>
    </div>
    <header class="hdr" id="hdr">
      <div class="container-x">
        <a class="logo" href="{{ route('home') }}"><img src="{{ asset('images/brand/delta-logo-horizontal.svg') }}" alt="Delta Insurance Services L.L.C" /></a>
        <ul class="nav">
          <li class="{{ $active === 'home' ? 'active' : '' }}"><a href="{{ route('home') }}">Home</a></li>
          <li class="{{ $active === 'services' ? 'active' : '' }}"><a href="{{ route('services') }}">Insurance <i class="far fa-angle-down"></i></a>
            <div class="mega">@foreach (config('site.services') as $key => $s)<a href="{{ route('products.'.$key) }}" class="{{ $s['online'] ? 'feat' : '' }}"><span class="ic"><i class="fal {{ $s['icon'] }}"></i></span><span><b>{{ $s['name'] }}@if ($s['online'])<span class="d-badge d-badge--online" style="padding:4px 8px;font-size:11px">Buy online</span>@endif</b><small>{{ $s['desc'] }}</small></span></a>@endforeach<div class="foot"><span>Not sure what you need?</span><a class="link-x" href="{{ route('contact') }}">Speak to an advisor <i class="far fa-arrow-right"></i></a></div></div>
          </li>
          <li class="{{ $active === 'motorquote' ? 'active' : '' }}"><a href="{{ url(config('site.links.motor_quote')) }}">Motor Quote</a></li>
          <li class="{{ $active === 'about' ? 'active' : '' }}"><a href="{{ route('about') }}">About Us</a></li>
          <li class="{{ $active === 'contact' ? 'active' : '' }}"><a href="{{ route('contact') }}">Contact</a></li>
        </ul>
        <div class="hdr-cta">
          <a class="acc-link" href="{{ url(config('site.links.login')) }}"><i class="fal fa-circle-user"></i><span data-account-label>Sign in</span></a>
          <a class="btn-x btn-navy sm" href="{{ url(config('site.links.motor_quote')) }}" ><span>Get a Quote</span><span class="ar"><i class="far fa-arrow-right"></i></span></a>
          <button class="burger" type="button" data-open-nav aria-label="Open menu"><i class="far fa-bars-staggered"></i></button>
        </div>
      </div>
    </header>
    <div class="mnav" aria-hidden="true">
      <div class="panel">
        <div class="top"><img src="{{ asset('images/brand/delta-logo-horizontal.svg') }}" alt="Delta" /><button class="close" type="button" aria-label="Close"><i class="fal fa-xmark"></i></button></div>
        <a class="it" href="{{ route('home') }}"><i class="fal fa-house"></i>Home</a>
        <div class="grp">Insurance</div>
        @foreach (config('site.services') as $key => $s)<a class="it" href="{{ route('products.'.$key) }}"><i class="fal {{ $s['icon'] }}"></i>{{ $s['name'] }}@if ($s['online'])<span class="d-badge d-badge--online ms-auto">Online</span>@endif</a>@endforeach

        <div class="grp">Company</div>
        <a class="it" href="{{ url(config('site.links.motor_quote')) }}"><i class="fal fa-scale-balanced"></i>Compare motor quotes</a>
        <a class="it" href="{{ route('about') }}"><i class="fal fa-building"></i>About Us</a>
        <a class="it" href="{{ route('contact') }}"><i class="fal fa-envelope"></i>Contact</a>
        <a class="it" href="{{ url(config('site.links.login')) }}"><i class="fal fa-circle-user"></i>My Account</a>
      </div>
    </div>
