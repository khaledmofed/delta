{{-- Public website layout. Converted from delta-website/_build/layout.py (site_page). --}}
@php($active = $active ?? '')
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="description" content="@yield('description')" />
    <meta name="theme-color" content="#122a43" />
    <title>@yield('title', 'Delta Insurance Services')</title>
    <link rel="icon" href="{{ asset('images/brand/delta-mark.png') }}" type="image/png" />
    <link rel="preload" href="{{ asset('assets/fonts/jakarta-var.woff2') }}" as="font" type="font/woff2" crossorigin />
    <link rel="preload" href="{{ asset('assets/fonts/dm-sans-var.woff2') }}" as="font" type="font/woff2" crossorigin />
    <link rel="stylesheet" href="{{ asset('assets/css/bootstrap.min.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/css/fontawesome.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/css/site.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/css/journey.css') }}" />
    @stack('styles')
  </head>
  <body class="@yield('body_class')">
@include('partials.site.header', ['active' => $active])
    <main>
@yield('content')
    </main>
@include('partials.site.footer')
@if (config('site.demo'))
@include('partials.site.demo-panel')
@endif
@if (session('lead_reference'))
    <script>window.DELTA_LEAD_SENT = @json(session('lead_reference'));</script>
@endif
@if (session('notice'))
    <script>window.DELTA_NOTICE = @json(session('notice'));</script>
@endif
    <script src="{{ asset('assets/js/delta-data.js') }}"></script>
    <script src="{{ asset('assets/js/delta-store.js') }}"></script>
    <script src="{{ asset('assets/js/delta.js') }}"></script>
    @stack('scripts')
  </body>
</html>
