{{-- Home hero right column + motor quote bar + insurer strip — same markup as delta-website/index.html,
     with real data instead of the prototype's sample prices and placeholder insurers:
     - floating card: the insurers Delta is connected to (config/insurers.php), "Live quotes" instead of a price
     - quote bar: number of insurers currently enabled for motor (InsurerManager, admin switch included)
     - logo strip: the same connected insurers; more appear here as they are added to config/insurers.php --}}
@php
    $connected = collect(config('insurers.providers'))->filter(fn ($p) => in_array('motor', $p['products'] ?? [], true));
    $enabled = count(rescue(fn () => app(\App\Insurers\InsurerManager::class)->forProduct('motor'), [], false));
    $logo = fn (string $code, string $name) => '<span class="ins-logo ins-logo--sm" title="'.e($name).'"><img src="'.e(asset("images/insurers/{$code}.png")).'" alt="'.e($name).'" data-pid="'.e($code).'" onerror="DELTA.util &amp;&amp; DELTA.util.logoFallback(this)" /></span>';
@endphp
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
                <small style="margin-bottom: 6px">Compare motor quotes</small>
@foreach ($connected as $code => $p)
                <div class="row-mini">{!! $logo($code, $p['name']) !!}{{ $p['short'] ?? $p['name'] }}<span class="p">Live quotes</span></div>
@endforeach
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
              ><small>{{ $enabled ? 'Quotes from '.$enabled.' '.\Illuminate\Support\Str::plural('insurer', $enabled).' in minutes' : 'Advisor-assisted quotes' }}</small>
            </div>
            <form data-quick-quote method="get" action="{{ route('motor.quote') }}">
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
            <span>Offers from leading UAE insurers</span>
            <div class="list">
@foreach ($connected as $code => $p)
              <span class="ins">{!! $logo($code, $p['name']) !!}<span class="nm">{{ $p['name'] }}</span></span>
@endforeach
            </div>
          </div>
