@extends('layouts.app')

@section('title', 'Payment — Delta Insurance')

{{-- Shown only when the returning browser has no open order in its session.
     The URL result is never trusted: the insurer is asked for the real status. --}}
@section('content')
    <div class="card">
        <h1>We are checking your payment with the insurer</h1>
        <p>We confirm every payment directly with the insurer. If you started your purchase in another browser, please continue there, or call us on {{ config('site.phone') }}.</p>
    </div>
@endsection
