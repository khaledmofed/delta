@extends('layouts.site', ['active' => 'motorquote'])

@section('title', 'Compare Motor Insurance Offers — Delta Insurance')
@section('description', 'Compare motor insurance offers.')

@section('content')


        <section class="d-flow">
          <div class="container-x">
            <div class="d-flow__head">
              <div><span class="eyebrow">Motor Insurance · Compare</span><h1 class="d-h2" id="resTitle">Your motor insurance offers</h1></div>
              <div class="d-flex gap-2"><a class="d-btn d-btn--ghost d-btn--sm" href="{{ route('motor.quote') }}?edit=1"><i class="fal fa-pen"></i> Edit details</a></div>
            </div>
            <ul class="d-stepper" style="padding:0"><li class="is-done" data-step-ind="1"><span class="n"><span>1</span></span>Quote</li><li class="is-active" data-step-ind="2"><span class="n"><span>2</span></span>Compare</li><li class="" data-step-ind="3"><span class="n"><span>3</span></span>Checkout</li><li class="" data-step-ind="4"><span class="n"><span>4</span></span>Payment</li><li class="" data-step-ind="5"><span class="n"><span>5</span></span>Policy</li></ul>
            <div class="d-sumbar" id="sumbar"></div>
            <div class="d-provstat" id="provstat"></div>
            <div class="d-results">
              <aside class="d-filters">
                <div class="d-card">
                  <div class="grp"><h4>Sort by</h4><select class="d-select" id="sortBy"><option value="price">Lowest price</option><option value="deductible">Lowest deductible</option><option value="benefits">Most benefits</option></select></div>
                  <div class="grp"><h4>Cover type</h4>
                    <label class="d-check"><input type="checkbox" data-f="type" value="comprehensive" checked /> Comprehensive</label>
                    <label class="d-check"><input type="checkbox" data-f="type" value="third_party" checked /> Third party</label></div>
                  <div class="grp"><h4>Repair</h4>
                    <label class="d-check"><input type="checkbox" data-f="repair" value="agency" checked /> Agency</label>
                    <label class="d-check"><input type="checkbox" data-f="repair" value="garage" checked /> Garage</label></div>
                  <div class="grp"><h4>Max price <span class="d-muted-2" id="maxPriceLbl" style="float:right;font-weight:500"></span></h4><input type="range" class="d-range" id="maxPrice" /></div>
                  <div class="grp"><h4>Must include</h4>
                    <label class="d-check"><input type="checkbox" data-f="need" value="roadside" /> Roadside assistance</label>
                    <label class="d-check"><input type="checkbox" data-f="need" value="rentACar" /> Rent-a-car</label>
                    <label class="d-check"><input type="checkbox" data-f="need" value="gcc" /> GCC cover</label>
                    <label class="d-check"><input type="checkbox" data-f="need" value="pa" /> Personal accident</label></div>
                  <div class="grp"><h4>Insurer</h4><div id="provFilter"></div></div>
                </div>
              </aside>
              <div>
                <div class="d-flex justify-content-between align-items-center mb-15"><span class="d-muted" id="resCount"></span><span class="d-muted-2" style="font-size:13px"><i class="fal fa-clock"></i> <span id="validity"></span></span></div>
                <div id="offers"></div>
              </div>
            </div>
          </div>
        </section>
        <div class="d-comparebar" id="compareBar"><span id="compareTxt">0 selected</span><button class="d-btn d-btn--ghost d-btn--sm" id="compareClear">Clear</button><button class="d-btn d-btn--primary d-btn--sm" id="compareGo">Compare side by side</button></div>
@endsection

@push('scripts')
    @php($motor = ['state' => $state, 'csrf' => csrf_token(), 'contactUrl' => route('contact'), 'quoteUrl' => route('motor.quote'), 'toast' => session('toast')])
    <script>window.MOTOR = @json($motor);</script>
    <script src="{{ asset('assets/js/pages/quote-results.js') }}"></script>
@endpush
