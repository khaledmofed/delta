@extends('layouts.site', ['active' => 'motorquote'])

@section('title', 'Policy Confirmation — Delta Insurance')
@section('description', 'Your motor insurance policy.')

@use('App\Support\MotorPresenter', 'P')
@use('App\Services\MotorOrders')
@php
    $ins = P::insurer($order->insurer);
    $in = $order->quoteRequest->input;
    $vehicle = trim(($in['year'] ?? '').' '.($in['make'] ?? '').' '.($in['model'] ?? ''));
    $reached = collect($order->history ?? [])->groupBy('state')->map(fn ($h) => $h->last()['at']);
    $failed = $order->state === 'PAYMENT_FAILED';
    $issued = $order->state === 'POLICY_ISSUED';
    $open = in_array($order->state, MotorOrders::OPEN_STATES, true);
    // Only steps that really happen today (no ERP / notification steps until those integrations exist).
    $steps = [
        ['QUOTE_SELECTED', 'Offer selected', $quote->plan_name.' · '.$ins['name']],
        ['PAYMENT_PENDING', 'Secure payment', 'On '.$ins['name'].'’s payment page'],
        ['PAYMENT_SUCCESS', 'Payment confirmed', 'Confirmed directly with the insurer'],
        ['POLICY_ISSUED', 'Policy issued', 'Policy number received from the insurer'],
    ];
    $activeStep = match (true) {
        $issued, $failed => null,
        in_array($order->state, ['PAYMENT_SUCCESS', 'INSURANCE_PROCESSING', 'POLICY_REQUESTED'], true) => 'POLICY_ISSUED',
        $order->state === 'PAYMENT_PENDING' => 'PAYMENT_SUCCESS',
        default => 'PAYMENT_PENDING',
    };
    [$heroIcon, $heroTitle, $heroText] = match (true) {
        $issued => ['fa-circle-check', 'You’re insured!', 'Your motor policy is active from <b style="color:var(--ink)">'.e(P::date($policy?->starts_on)).'</b>. Policy number <b style="color:var(--ink)">'.e($policy?->policy_no).'</b>.'],
        $failed => ['fa-circle-exclamation', 'Payment not completed', 'The insurer reported the payment as declined. No policy was issued. <a class="d-link" href="'.e(route('motor.payment', $order)).'">Try the payment again</a>'],
        $order->state === 'PAYMENT_PENDING' => ['fa-hourglass-half', 'Confirming your payment', 'We are checking your payment with '.e($ins['name']).'. This page updates automatically. Not paid yet? <a class="d-link" href="'.e(route('motor.payment', $order)).'">Go to payment</a>'],
        $order->state === 'QUOTE_SELECTED' => ['fa-hourglass-half', 'Payment pending', 'This order is not paid yet. <a class="d-link" href="'.e(route('motor.checkout', $order)).'">Continue to payment</a>'],
        $open => ['fa-hourglass-half', 'Payment received', 'We are requesting your policy from the insurer. This page updates automatically.'],
        default => ['fa-circle-info', 'Order '.$order->reference, 'Please contact us on '.e(config('site.phone')).' about this order.'],
    };
@endphp

@section('content')
        <section class="d-flow">
          <div class="container-x">
            @include('partials.motor.stepper', ['active' => 5])
            <div class="row g-4">
              <div class="col-xl-7">
                <div class="d-panel">
                  <div class="d-success-hero">
                    <div class="ic {{ $issued ? '' : 'wait' }}" id="heroIc"><i class="fal {{ $heroIcon }}"></i></div>
                    <h1 class="d-h2" id="heroTitle">{{ $heroTitle }}</h1>
                    <p class="d-lead mx-auto mt-10" id="heroText">{!! $heroText !!}</p>
                  </div>
                  <div class="d-divider"></div>
                  <h4 class="mb-20" style="color:#fff;font-size:17px">Application status</h4>
                  <ul class="d-timeline" id="timeline">@foreach ($steps as [$id, $label, $sub])@php
                      $done = $reached->has($id) && ! ($failed && $id === 'PAYMENT_SUCCESS');
                      $warn = $failed && $id === 'PAYMENT_SUCCESS';
                      $cls = $warn ? 'warn' : ($done ? 'done' : ($id === $activeStep ? 'active' : ''));
                      $icon = $warn ? 'fa-triangle-exclamation' : ($done ? 'fa-check' : ($id === $activeStep ? 'fa-spinner-third' : 'fa-circle-small'));
                      $at = $done ? ' · '.\Illuminate\Support\Carbon::parse($reached[$id])->timezone(config('app.timezone'))->format('H:i:s') : '';
                  @endphp<li class="{{ $cls }}"><span class="dot"><i class="far {{ $icon }}"></i></span><b>{{ $label }}</b><small>{{ $warn ? 'Declined at the insurer — you can try again' : $sub }}{{ $at }}</small></li>@endforeach</ul>
                </div>
              </div>
              <div class="col-xl-5">
                <div class="d-aside">
                  <div id="policyBox">@if ($policy)<div class="d-policy-card"><img src="{{ asset('images/brand/delta-glyph-white.svg') }}" alt="" /><small>Policy number</small><b style="font-size:22px">{{ $policy->policy_no }}</b><small>Insurer</small><b>{{ $ins['name'] }} — {{ $quote->plan_name }}</b><div class="row"><div class="col-6"><small>Start</small><b>{{ P::date($policy->starts_on) }}</b></div><div class="col-6"><small>End</small><b>{{ P::date($policy->ends_on) }}</b></div></div><small>Vehicle</small><b style="margin:0">{{ $vehicle }}</b></div>@else<div class="d-card"><h4 style="color:var(--ink);font-size:16px">Your policy</h4><p class="d-muted mb-0">Your policy details will appear here once issued.</p></div>@endif</div>
                  <div class="d-card mt-3" id="orderBox"><h4 style="color:var(--ink);font-size:16px;margin-bottom:12px">Order {{ $order->reference }}</h4><div class="d-kv"><span>Vehicle</span><b>{{ $vehicle }}</b></div><div class="d-kv"><span>{{ $issued ? 'Total paid' : 'Total' }}</span><b>{{ P::money($order->total) }}</b></div>@foreach ($order->payments()->orderBy('attempt')->get() as $p)<div class="d-kv"><span>Payment #{{ $p->attempt }} · {{ $ins['name'] }}</span><b style="color:{{ $p->status === 'success' ? 'var(--ok)' : ($p->status === 'failed' ? 'var(--bad)' : 'var(--steel)') }}">{{ $p->status }}</b></div>@endforeach</div>
                </div>
              </div>
            </div>
          </div>
        </section>
@endsection

@push('scripts')
@if ($open)
    <script>
      /* Re-check with the insurer (server throttles the insurer call) and reload when the order moves on. */
      (function () {
        var state = @json($order->state), url = @json(route('motor.order.status', $order)), n = 0;
        function tick() {
          if (++n > 120) return;
          setTimeout(function () {
            fetch(url, { headers: { Accept: "application/json" }, credentials: "same-origin" })
              .then(function (r) { return r.json(); })
              .then(function (s) { if (s.state !== state) location.reload(); else tick(); })
              .catch(tick);
          }, n < 6 ? 5000 : 15000);
        }
        tick();
      })();
    </script>
@endif
@endpush
