@extends('layouts.site', ['active' => 'motorquote'])

@section('title', $quote->plan_name.' — Plan details — Delta Insurance')
@section('description', 'Covers, excess and key terms of this motor insurance offer.')

@use('App\Support\MotorPresenter', 'P')
@php
    $ins = P::insurer($quote->insurer);
    $comp = $quote->cover_type === 'comprehensive';
    $mandatory = collect($quote->covers ?? [])->where('type', 'mandatory');
    $optional = collect($quote->covers ?? [])->where('type', 'optional');
    $indicative = (bool) ($quote->raw['indicative'] ?? false);
    $excess = $quote->deductible !== null ? P::money0($quote->deductible) : ($comp ? 'Confirmed by the insurer before payment' : 'Not applicable (third party)');
    $repair = $quote->repair_type ? ($quote->repair_type === 'agency' ? 'Agency repair' : 'Garage repair') : '—';
    $in = $qr->input;
    $start = isset($in['policy_start']) ? \Illuminate\Support\Carbon::parse($in['policy_start']) : null;
    $chosen = collect($quote->covers ?? [])->whereIn('code', $addons);
    $expired = $quote->valid_until && $quote->valid_until->isPast();
@endphp

{{-- Plan details (deck slide 7): every cover the insurer returned, excess and key terms, with the selected plan kept visible.
     Built from the journey components (d-flow, d-panel, d-ctable, d-aside). Only insurer data — no invented benefits or exclusions. --}}
@section('content')
        <section class="d-flow">
          <div class="container-x">
            <div class="d-flow__head">
              <div><span class="eyebrow">Motor Insurance · Plan details</span><h1 class="d-h2">{{ $quote->plan_name }}</h1></div>
              <a class="d-btn d-btn--ghost d-btn--sm" href="{{ route('motor.results', $qr) }}"><i class="far fa-arrow-left"></i> Back to all offers</a>
            </div>
            @include('partials.motor.stepper', ['active' => 2])
            <div class="row g-4">
              <div class="col-xl-8">
                <div class="d-panel">
                  <div class="d-panel__title"><i class="fal fa-shield-check"></i><div><h2>Coverage summary</h2><p>Know exactly what you are buying — as returned by {{ $ins['name'] }} for your {{ trim(($in['year'] ?? '').' '.($in['make'] ?? '').' '.($in['model'] ?? '')) }}.</p></div></div>
                  <div class="d-table-wrap" style="box-shadow:none">
                    <table class="d-ctable" id="coverTable">
                      <thead><tr><th>Benefit</th><th>What is included</th><th style="text-align:right">Premium</th></tr></thead>
                      <tbody>
                        <tr><td>Cover type</td><td class="hl">{{ $comp ? 'Comprehensive' : 'Third party liability' }}</td><td></td></tr>
                        @if ($comp)<tr><td>Repair basis</td><td>{{ $repair }}</td><td></td></tr>@endif
                        <tr><td>Sum insured</td><td>{{ $quote->sum_insured ? P::money0($quote->sum_insured) : '—' }}</td><td></td></tr>
                        <tr><td>Policy excess</td><td>{{ $excess }}</td><td></td></tr>
                        @foreach ($mandatory as $c)
                        <tr><td>{{ $c['name'] }}</td><td><span class="y"><i class="far fa-check"></i> Included</span></td><td style="text-align:right">{{ (float) $c['premium'] > 0 ? P::money($c['premium']) : '—' }}</td></tr>
                        @endforeach
                        @foreach ($optional as $c)
                        <tr><td>{{ $c['name'] }}</td><td>@if (in_array((string) $c['code'], $addons, true))<span class="y"><i class="far fa-check"></i> Optional — added (you asked for it)</span>@else<span class="n"><i class="far fa-plus"></i> Optional — not included</span>@endif</td><td style="text-align:right">+{{ P::money($c['premium']) }}</td></tr>
                        @endforeach
                        @if ($mandatory->isEmpty() && $optional->isEmpty())
                        <tr><td>Covers</td><td colspan="2" class="n">The insurer did not list individual covers for this plan.</td></tr>
                        @endif
                      </tbody>
                    </table>
                  </div>

                  <div class="d-subhead">Key terms</div>
                  <ul class="check-list">
                    <li><i class="fas fa-check"></i><span><b>{{ $indicative ? 'Indicative price' : 'Price' }}</b> — {{ $indicative ? 'the insurer confirms the final price and excess before you pay.' : 'as quoted by the insurer.' }} All prices include 5% VAT.</span></li>
                    <li><i class="fas fa-check"></i><span><b>Offer validity</b> — valid until {{ P::date($quote->valid_until) }}.</span></li>
                    <li><i class="fas fa-check"></i><span><b>Policy period</b> — 12 months{{ $start ? ' from '.$start->format('d M Y') : '' }}.</span></li>
                    <li><i class="fas fa-check"></i><span><b>Excess</b> — {{ $excess }}{{ $quote->deductible !== null ? ' per claim' : '' }}.</span></li>
                    <li><i class="fas fa-check"></i><span><b>Verification</b> — cover is subject to {{ $ins['name'] }}’s policy wording, exclusions and the check of the documents you uploaded.</span></li>
                  </ul>
                  <div class="d-note mt-20"><i class="fal fa-file-lines"></i><div>Need the full policy wording or the list of exclusions before you buy? <a class="d-link" href="{{ route('contact') }}">Ask an advisor</a> or call <a class="d-link" href="tel:{{ config('site.phone_href') }}">{{ config('site.phone') }}</a> — we send them to you.</div></div>
                </div>
              </div>
              <div class="col-xl-4">
                <div class="d-aside">
                  <div class="d-card d-card--glow">
                    <span class="eyebrow">Your selected plan</span>
                    <div class="d-flex gap-3 align-items-center mt-15 mb-15">@include('partials.motor.logo', ['ins' => $ins, 'size' => 'lg'])<div><b style="color:var(--ink);font-size:17px;display:block">{{ $quote->plan_name }}</b><small class="d-muted-2">{{ $ins['name'] }}</small></div></div>
                    <div style="font-family:var(--font-head);font-size:36px;font-weight:800;color:var(--ink);letter-spacing:-.04em;line-height:1">{{ P::money0($withAddons['total']) }}</div>
                    <small class="d-muted-2">incl. VAT · per year{{ $chosen->isNotEmpty() ? ' · with your add-ons' : '' }}</small>
                    <div class="d-divider" style="margin:16px 0"></div>
                    <div class="d-kv"><span>Premium</span><b>{{ P::money($quote->premium) }}</b></div>
                    @foreach ($chosen as $c)<div class="d-kv"><span>+ {{ $c['name'] }}</span><b>{{ P::money($c['premium']) }}</b></div>@endforeach
                    <div class="d-kv"><span>VAT 5%</span><b>{{ P::money($withAddons['vat']) }}</b></div>
                    <div class="d-kv"><span>Excess</span><b>{{ $quote->deductible !== null ? P::money0($quote->deductible) : '—' }}</b></div>
                    @if ($comp)<div class="d-kv"><span>Repair</span><b>{{ $repair }}</b></div>@endif
                    @if ($expired)
                      <p class="d-muted-2 mt-15 mb-0">This offer has expired. <a class="d-link" href="{{ route('motor.quote') }}">Request a new quote</a>.</p>
                    @else
                      <form method="post" action="{{ route('motor.select', $quote) }}" class="mt-20">@csrf<button class="d-btn d-btn--primary" style="width:100%;justify-content:center" type="submit">Continue with this plan <i class="far fa-arrow-right"></i></button></form>
                    @endif
                    <p class="d-muted-2 mt-10 mb-0" style="font-size:12.5px;text-align:center">Full terms are confirmed by the insurer before payment.</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </section>
@endsection
