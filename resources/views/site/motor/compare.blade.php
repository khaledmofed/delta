@extends('layouts.site', ['active' => 'motorquote'])

@section('title', 'Compare Offers — Delta Insurance')
@section('description', 'Side-by-side motor insurance comparison.')

@use('App\Support\MotorPresenter', 'P')
@php
    $min = $quotes->min(fn ($q) => (float) $q->total);
    $best = fn ($q) => (float) $q->total === $min ? 'best' : '';
    // One row per cover name across the compared offers, in the order the insurers list them.
    $coverNames = $quotes->flatMap(fn ($q) => array_column($q->covers ?? [], 'name'))->unique()->values();
    $cover = fn ($q, $name) => collect($q->covers ?? [])->firstWhere('name', $name);
    $yes = fn ($txt = 'Included') => '<span class="y"><i class="far fa-check"></i> '.e($txt).'</span>';
    $no = '<span class="n"><i class="far fa-xmark"></i> Not included</span>';
@endphp

@section('content')
        <section class="d-flow">
          <div class="container-x">
            <div class="d-flow__head">
              <div><span class="eyebrow">Motor Insurance · Side by side</span><h1 class="d-h2">Compare offers</h1></div>
              <a class="d-btn d-btn--ghost d-btn--sm" id="backToResults" href="{{ route('motor.results', $qr) }}"><i class="far fa-arrow-left"></i> Back to all offers</a>
            </div>
            @include('partials.motor.stepper', ['active' => 2])
            <div class="d-table-wrap"><table class="d-ctable" id="ctable">
@if ($quotes->count() < 2)
              <tr><td style="padding:40px;text-align:center" colspan="4">Select at least two offers on the results page to compare. <a class="d-link" href="{{ route('motor.results', $qr) }}">Back to offers</a></td></tr>
@else
              <thead><tr><th></th>@foreach ($quotes as $q)<th class="{{ $best($q) }}"><div style="color:var(--ink);font-size:16px;font-weight:600">{{ $q->plan_name }}</div><div style="font-family:var(--font-head);font-size:30px;color:var(--ink);letter-spacing:-.04em;margin:8px 0 4px">{{ P::money0($q->total) }}</div><small class="d-muted-2">incl. VAT</small>@if ($best($q))<div class="mt-10"><span class="d-badge d-badge--online">Lowest price</span></div>@endif</th>@endforeach</tr></thead>
              <tbody>
                <tr><td>Insurer</td>@foreach ($quotes as $q)<td class="{{ $best($q) }}"><div class="d-flex align-items-center gap-2">@include('partials.motor.logo', ['ins' => P::insurer($q->insurer), 'size' => 'sm']){{ P::insurer($q->insurer)['name'] }}</div></td>@endforeach</tr>
                <tr><td>Cover type</td>@foreach ($quotes as $q)<td class="{{ $best($q) }}">{{ $q->cover_type === 'comprehensive' ? 'Comprehensive' : 'Third party' }}</td>@endforeach</tr>
                <tr><td>Repair</td>@foreach ($quotes as $q)<td class="{{ $best($q) }}">{{ $q->repair_type ? ($q->repair_type === 'agency' ? 'Agency' : 'Garage') : '—' }}</td>@endforeach</tr>
                <tr><td>Sum insured</td>@foreach ($quotes as $q)<td class="{{ $best($q) }}">{{ $q->sum_insured ? P::money0($q->sum_insured) : '—' }}</td>@endforeach</tr>
                <tr><td>Deductible / excess</td>@foreach ($quotes as $q)<td class="{{ $best($q) }}">{{ $q->cover_type === 'comprehensive' && $q->deductible !== null ? P::money0($q->deductible) : ($q->cover_type === 'comprehensive' ? 'Confirmed at checkout' : '—') }}</td>@endforeach</tr>
@foreach ($coverNames as $name)
                <tr><td>{{ $name }}</td>@foreach ($quotes as $q)@php($c = $cover($q, $name))<td class="{{ $best($q) }}">{!! ! $c ? $no : ($c['type'] === 'mandatory' ? $yes() : $yes('Optional +'.P::money0($c['premium']))) !!}</td>@endforeach</tr>
@endforeach
                <tr><td>Premium</td>@foreach ($quotes as $q)<td class="{{ $best($q) }} hl">{{ P::money($q->premium) }}</td>@endforeach</tr>
                <tr><td>VAT (5%)</td>@foreach ($quotes as $q)<td class="{{ $best($q) }}">{{ P::money($q->vat) }}</td>@endforeach</tr>
                <tr><td>Valid until</td>@foreach ($quotes as $q)<td class="{{ $best($q) }}">{{ P::date($q->valid_until) }}</td>@endforeach</tr>
                <tr><td></td>@foreach ($quotes as $q)<td class="{{ $best($q) }}"><form method="post" action="{{ route('motor.select', $q) }}">@csrf<button class="d-btn d-btn--primary d-btn--sm" data-select="{{ $q->id }}">Select this offer</button></form></td>@endforeach</tr>
              </tbody>
@endif
            </table></div>
            <p class="d-muted-2 mt-15" style="font-size:13px">All prices in AED and include 5% VAT. Offers are subject to the insurer’s terms, conditions and document verification.</p>
          </div>
        </section>
@endsection
