@extends('layouts.site', ['active' => 'motorquote'])

@section('title', 'Checkout — Delta Insurance')
@section('description', 'Complete your motor insurance application.')

@use('App\Support\MotorPresenter', 'P')
@php
    $in = $qr->input;
    $ins = P::insurer($order->insurer);
    $cap = P::captured($qr);
    $comp = $quote->cover_type === 'comprehensive';
    $field = fn ($name) => $errors->has($name) ? ' is-invalid' : '';
@endphp

@section('content')
        <section class="d-flow">
          <div class="container-x">
            <div class="d-flow__head">
              <div><span class="eyebrow">Motor Insurance · Application</span><h1 class="d-h2">Complete your application</h1></div>
              <a class="d-btn d-btn--ghost d-btn--sm" href="{{ route('motor.results', $qr) }}"><i class="far fa-arrow-left"></i> Change offer</a>
            </div>
            @include('partials.motor.stepper', ['active' => 3])
            <div class="row g-4">
              <div class="col-xl-8">
                <form id="checkoutForm" class="d-panel" novalidate method="post" action="{{ route('motor.checkout.submit', $order) }}">
                  @csrf
                  @include('partials.motor.alert')
                  <div class="d-panel__title"><i class="fal fa-user-check"></i><div><h2>Policyholder</h2><p>Must match the Emirates ID you upload.</p></div></div>
                  <div class="row g-3">
                    <div class="col-md-6"><label class="d-field"><span>Full name <em>*</em></span><input class="d-input{{ $field('name') }}" name="name" required value="{{ old('name', $order->customer->name ?? $in['name_en']) }}" /><small class="d-error">{{ $errors->first('name') ?: 'Required.' }}</small></label></div>
                    <div class="col-md-6"><label class="d-field"><span>Email <em>*</em></span><input class="d-input{{ $field('email') }}" type="email" name="email" required value="{{ old('email', $order->customer->email ?? $in['email']) }}" /><small class="d-error">{{ $errors->first('email') ?: 'Required.' }}</small></label></div>
                    <div class="col-md-6"><label class="d-field"><span>Mobile <em>*</em></span><div class="d-input-group{{ $field('mobile') }}"><span class="pre">+971</span><input class="d-input{{ $field('mobile') }}" name="mobile" required value="{{ old('mobile', preg_replace('/^971/', '', $order->customer->mobile ?? $in['mobile'])) }}" /></div><small class="d-error">{{ $errors->first('mobile') ?: 'Required.' }}</small></label></div>
                    <div class="col-md-6"><label class="d-field"><span>Policy start date</span><input class="d-input{{ $field('policyStart') }}" type="date" name="policyStart" value="{{ old('policyStart', $order->policy_start?->toDateString() ?? $in['policy_start']) }}" min="{{ today()->toDateString() }}" max="{{ today()->addDays(30)->toDateString() }}" /><small class="d-error">{{ $errors->first('policyStart') ?: 'Choose a date within the next 30 days.' }}</small></label></div>
                  </div>
                  <div class="d-panel__title mt-40"><i class="fal fa-car"></i><div><h2>Vehicle &amp; documents</h2><p>Already sent to the insurer with your quote request.</p></div><a class="d-btn d-btn--ghost d-btn--sm ms-auto" href="{{ route('motor.quote') }}?edit=1"><i class="fal fa-pen"></i> Edit</a></div>
                  <div class="d-review d-review--2" id="capturedBox"><div class="d-card"><h5>Vehicle</h5>@foreach (['Vehicle' => $cap['vehicle'], 'Chassis' => $cap['chassis'], 'Plate' => $cap['plate'], 'Traffic file' => $cap['tcf'], 'Emirates ID' => $cap['emiratesId'], 'Licence' => $cap['licence']] as $k => $v)<div class="d-kv"><span>{{ $k }}</span><b>{{ $v ?: '—' }}</b></div>@endforeach</div><div class="d-card"><h5>Documents</h5>@forelse ($cap['documents'] as $d)<div class="d-kv"><span><i class="fas fa-circle-check" style="color:#00894f"></i> {{ $d['label'] }}</span><b>{{ $d['file'] }}</b></div>@empty<p class="d-muted mb-0">No documents on file.</p>@endforelse</div></div>
                  <div class="d-panel__title mt-40"><i class="fal fa-file-signature"></i><div><h2>Declarations</h2><p>Required by the insurer.</p></div></div>
                  <label class="d-check mb-10"><input type="checkbox" name="d1" required @checked(old('d1')) /> The vehicle is in good condition, has no existing damage and is used for private purposes only.</label>
                  <label class="d-check mb-10"><input type="checkbox" name="d2" required @checked(old('d2')) /> I have not had an insurance policy cancelled or a claim declined in the last 3 years.</label>
                  <label class="d-check"><input type="checkbox" name="d3" required @checked(old('d3')) /> I have read the policy wording and key facts of the selected offer.</label>
                  <small class="d-error" id="declErr" @if ($errors->hasAny(['d1', 'd2', 'd3'])) style="display:block" @endif>Please confirm all declarations.</small>
                  <div class="d-actions">
                    <span class="d-muted-2" style="font-size:13px;align-self:center"><i class="fal fa-lock"></i> You pay on {{ $ins['name'] }}’s secure payment page.</span>
                    <button type="submit" class="d-btn d-btn--primary">Continue to secure payment <i class="far fa-lock"></i></button>
                  </div>
                </form>
              </div>
              <div class="col-xl-4"><div class="d-aside" id="orderAside">@include('partials.motor.order-aside')</div></div>
            </div>
          </div>
        </section>
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/pages/checkout.js') }}"></script>
@endpush
