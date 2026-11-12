{{-- Home hero right column + motor quote bar + insurer strip — same markup as delta-website/index.html,
     with real data instead of the prototype's sample prices and placeholder insurers:
     - floating card: the insurers marked Live in the insurers table, "Live quotes" instead of a price
     - quote bar: number of Live insurers that are also enabled for motor quoting (InsurerManager, admin switch included)
     - logo strip: every insurer in the table, Live ones and Coming soon ones (partials/site/insurer-strip) --}}
@php
    $stripInsurers = \App\Models\Insurer::forWebsite();
    $live = $stripInsurers->filter->isLive();
    $quoting = array_keys(rescue(fn () => app(\App\Insurers\InsurerManager::class)->forProduct('motor'), [], false));
    $enabled = count(array_intersect($quoting, $live->pluck('code')->all()));
    $connected = $live->mapWithKeys(fn ($i) => [$i->code => ['name' => $i->name, 'short' => config("insurers.providers.{$i->code}.short")]]);
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
@include('partials.site.insurer-strip')
