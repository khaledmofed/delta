    <div class="topbar">
      <div class="container-x">
        <div class="l"><span><i class="fal fa-location-dot"></i>{{ config('site.location') }}</span><span class="hide-sm"><i class="fal fa-clock"></i>Insurance brokers since 1990</span></div>
        <div class="r"><a class="hide-sm" href="{{ route('track') }}"><i class="fal fa-magnifying-glass-location"></i>Track my order</a><a href="tel:{{ config('site.phone_href') }}"><i class="fal fa-phone"></i>{{ config('site.phone') }}</a><a class="hide-sm" href="mailto:{{ config('site.email') }}"><i class="fal fa-envelope"></i>{{ config('site.email') }}</a></div>
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
          <li class="{{ $active === 'claims' ? 'active' : '' }}"><a href="{{ route('claims') }}">Claims &amp; Support</a></li>
          <li class="{{ $active === 'about' ? 'active' : '' }}"><a href="{{ route('about') }}">About Us</a></li>
          <li class="{{ $active === 'contact' ? 'active' : '' }}"><a href="{{ route('contact') }}">Contact</a></li>
        </ul>
        <div class="hdr-cta">
{{-- No customer accounts: "Track my order", or the order(s) opened in this session (JourneyAccess, AppServiceProvider composer). --}}
@if (! $myOrder)
          <a class="acc-link" href="{{ route('track') }}"><i class="fal fa-magnifying-glass-location"></i><span>Track my order</span></a>
@else
          <div class="acc-menu" data-acc-menu>
            <button class="acc-link" type="button" aria-haspopup="true" aria-expanded="false"><i class="fal fa-circle-user"></i><span class="acc-tx" aria-label="My order · {{ $myOrder->reference }}"><small>My order<i class="sep"> · </i></small><b>{{ $myOrder->reference }}</b></span><i class="far fa-angle-down acc-caret"></i></button>
            <div class="acc-drop">
@if ($myOrders->count() > 1)
              <div class="lbl">Your orders</div>
@foreach ($myOrders as $o)
              <a class="ord {{ $loop->first ? 'on' : '' }}" href="{{ route('motor.confirmation', $o) }}"><span class="ic"><i class="fal fa-file-certificate"></i></span><span><b>{{ $o->reference }}</b><small>{{ $o->customerStatus() }}</small></span></a>
@endforeach
@else
              <a href="{{ route('motor.confirmation', $myOrder) }}"><span class="ic"><i class="fal fa-file-certificate"></i></span><span><b>View my order</b><small>{{ $myOrder->customerStatus() }}</small></span></a>
@endif
              <a href="{{ route('track') }}"><span class="ic"><i class="fal fa-magnifying-glass-location"></i></span><span><b>Track another order</b></span></a>
              <form method="post" action="{{ route('track.exit') }}">@csrf<button type="submit"><span class="ic"><i class="fal fa-arrow-right-from-bracket"></i></span><span><b>Exit</b><small>Close the order on this device</small></span></button></form>
            </div>
          </div>
@endif
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
        <a class="it" href="{{ route('claims') }}"><i class="fal fa-life-ring"></i>Claims &amp; Support</a>
        <a class="it" href="{{ route('about') }}"><i class="fal fa-building"></i>About Us</a>
        <a class="it" href="{{ route('contact') }}"><i class="fal fa-envelope"></i>Contact</a>
@if (! $myOrder)
        <a class="it" href="{{ route('track') }}"><i class="fal fa-magnifying-glass-location"></i>Track my order</a>
@else
        <div class="grp">My order</div>
@if ($myOrders->count() > 1)
@foreach ($myOrders as $o)
        <a class="it" href="{{ route('motor.confirmation', $o) }}"><i class="fal fa-file-certificate"></i><span>{{ $o->reference }}<small class="d-block" style="color:var(--muted);font-size:12.5px">{{ $o->customerStatus() }}</small></span></a>
@endforeach
@else
        <a class="it" href="{{ route('motor.confirmation', $myOrder) }}"><i class="fal fa-file-certificate"></i><span>View my order · {{ $myOrder->reference }}<small class="d-block" style="color:var(--muted);font-size:12.5px">{{ $myOrder->customerStatus() }}</small></span></a>
@endif
        <a class="it" href="{{ route('track') }}"><i class="fal fa-magnifying-glass-location"></i>Track another order</a>
        <form method="post" action="{{ route('track.exit') }}">@csrf<button class="it" type="submit"><i class="fal fa-arrow-right-from-bracket"></i>Exit</button></form>
@endif
      </div>
    </div>
