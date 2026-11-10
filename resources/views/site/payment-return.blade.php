@extends('layouts.app')

@section('title', 'Payment — Delta Insurance')

@section('content')
    <div class="card">
        @if ($result === 'success')
            <h1>Thank you — we are confirming your payment</h1>
            <p>We are requesting your policy from the insurer. You will receive it by email shortly.</p>
        @else
            <h1>Payment was not completed</h1>
            <p>No amount was charged. You can try again or contact us on +971 6 574 7420.</p>
        @endif
    </div>
@endsection
