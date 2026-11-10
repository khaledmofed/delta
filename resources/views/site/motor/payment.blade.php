@extends('layouts.site', ['active' => 'motorquote'])

@section('title', 'Secure Payment — Delta Insurance')
@section('description', 'Secure payment.')

@use('App\Support\MotorPresenter', 'P')
@php
    $ins = P::insurer($order->insurer);
    $attempts = $order->payments()->count();
    $in = $order->quoteRequest->input;
@endphp

{{-- Payment itself happens on the insurer's hosted page (DIN paymentUrl / AWNIC initializePolicyGeneration).
     This page summarises the order and sends the customer there; it is also where a failed payment is retried. --}}
@section('content')
        <section class="d-flow">
          <div class="container-x">
            @include('partials.motor.stepper', ['active' => 4])
            <div class="d-gw">
              <div class="d-gw__top"><span><i class="fas fa-lock me-2"></i>Secure hosted payment page · {{ $ins['name'] }}</span><span>Broker: Delta Insurance Services L.L.C</span></div>
              <div class="d-gw__main">
                <div class="err" id="gwErr" @if ($order->state === 'PAYMENT_FAILED' || $errors->has('checkout')) style="display:block" @endif>@if ($errors->has('checkout')){{ $errors->first('checkout') }}@elseif ($order->state === 'PAYMENT_FAILED')<b>Your last payment was not completed.</b> The insurer reported the payment as declined. No policy was issued — you can try again.@endif</div>
                <div class="d-flex justify-content-between align-items-start mb-20"><div><h3>Pay by card</h3><p class="mb-0">You enter your card details on {{ $ins['name'] }}’s secure payment page — Delta never sees or stores card data.</p></div><div class="cards"><span>VISA</span><span>MC</span><span>AMEX</span></div></div>
                <form id="payForm" method="post" action="{{ route('motor.pay', $order) }}">
                  @csrf
                  <div class="row g-3">
                    <div class="col-12"><button class="pay" type="submit" id="payBtn">Pay {{ P::money($order->total) }} on {{ $ins['name'] }}</button></div>
                  </div>
                </form>
                <p class="mt-15 mb-0" style="font-size:12.5px"><i class="fas fa-shield-halved me-1"></i> After paying you return here and we confirm the payment directly with the insurer.</p>
              </div>
              <div class="d-gw__side" id="gwSide"><p style="font-size:12px;text-transform:uppercase;letter-spacing:.08em;margin-bottom:6px">Order summary</p><h3 style="margin-bottom:14px">{{ $quote->plan_name }}</h3><div class="kv"><span>Insurer</span><b>{{ $ins['name'] }}</b></div><div class="kv"><span>Vehicle</span><b>{{ trim(($in['year'] ?? '').' '.($in['make'] ?? '').' '.($in['model'] ?? '')) }}</b></div><div class="kv"><span>Order</span><b>{{ $order->reference }}</b></div><div class="kv"><span>Premium</span><b>{{ P::money($order->premium) }}</b></div><div class="kv"><span>VAT 5%</span><b>{{ P::money($order->vat) }}</b></div><div class="kv total"><span>Total</span><b>{{ P::money($order->total) }}</b></div>@if ($attempts)<p style="font-size:12.5px;margin-top:14px">Previous attempts: {{ $attempts }}</p>@endif</div>
            </div>
          </div>
        </section>
        <div class="d-overlay" id="payOverlay"><div class="d-overlay__box"><div class="d-spinner"></div><h3 id="payMsg">Opening the secure payment page…</h3><p class="d-muted mb-0">Please do not close this window.</p></div></div>
@endsection

@push('scripts')
    <script>
      document.getElementById("payForm").addEventListener("submit", function () {
        document.getElementById("payOverlay").classList.add("open");
        document.getElementById("payBtn").disabled = true;
      });
    </script>
@endpush
