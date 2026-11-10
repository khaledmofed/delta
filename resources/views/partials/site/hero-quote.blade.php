{{-- Home hero: quick motor quote (deck slide 3). Same card as the motor page form; delta.js fills make/model/year
     and sends every filled field to /motor-quote, where motor-quote.js prefills the wizard. --}}
@php
    $plateSources = rescue(fn () => \App\Models\InsurerCode::where('insurer', 'din')->where('type', 'plateSource')->orderBy('label')->pluck('label', 'code'), collect(), false);
@endphp
            <div class="rv" id="quote">
              <form class="d-card d-card--glow" data-quick-quote method="get" action="{{ route('motor.quote') }}" style="padding:34px">
                <span class="d-badge d-badge--online mb-15">Online now</span>
                <h3 class="d-h3 mb-10 mt-10">Get your motor insurance quote</h3>
                <p class="d-muted-2 mb-20">Start with your car. Compare offers from our partner insurers in a few steps.</p>
                <div class="row g-3">
                  <div class="col-md-6"><label class="d-field"><span>Car make</span><select class="d-select" name="make" aria-label="Make"></select></label></div>
                  <div class="col-md-6"><label class="d-field"><span>Model</span><select class="d-select" name="model" aria-label="Model"><option value="">Select make first</option></select></label></div>
                  <div class="col-md-6"><label class="d-field"><span>Model year</span><select class="d-select" name="year" aria-label="Year"></select></label></div>
                  @if ($plateSources->isNotEmpty())
                  <div class="col-md-6"><label class="d-field"><span>Registration emirate</span><select class="d-select" name="plateSource" aria-label="Registration emirate"><option value="">Select</option>@foreach ($plateSources as $code => $label)<option value="{{ $code }}">{{ $label }}</option>@endforeach</select></label></div>
                  @endif
                  <div class="col-12 mt-10"><button class="btn-x btn-navy" style="width:100%" type="submit"><span>Continue to quotes</span><span class="ar"><i class="far fa-arrow-right"></i></span></button></div>
                </div>
                <p class="d-muted-2 mt-15 mb-0" style="font-size:13px;text-align:center"><i class="fal fa-lock"></i> No account needed to start</p>
              </form>
            </div>
