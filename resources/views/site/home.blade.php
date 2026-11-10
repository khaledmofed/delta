@extends('layouts.app')

@section('title', 'Delta Insurance — '.strtoupper(config('app.env')))

@section('content')
    <div class="card">
        <span class="badge">{{ strtoupper(config('app.env')) }} server is running</span>
        <h1 style="margin-top:14px">Delta Insurance Services L.L.C</h1>
        <p>Online insurance portal — backend and insurer integrations. The customer website (converted from the approved HTML prototype) will be published here.</p>
        <p style="font-size:13px">Laravel {{ app()->version() }} · PHP {{ PHP_VERSION }}</p>
    </div>
@endsection
