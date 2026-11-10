{{-- Admin console layout — converted from delta-website/admin.html (d-shell / d-side / d-top). --}}
@php
    $user = auth()->user();
    $failedOrders = $user->can('orders.view') ? \App\Models\Order::where('state', 'PAYMENT_FAILED')->count() : 0;
    $newLeads = $user->can('leads.view') ? \App\Models\Lead::where('status', 'new')->count() : 0;
    // [area, route, permission, icon, label, count, alert]
    $nav = [
        ['Overview', 'admin.dashboard', null, 'fa-gauge-high', 'Dashboard', 0, false],
        ['Insurance', 'admin.insurers', 'providers.view', 'fa-building-shield', 'Providers', 0, false],
        ['Insurance', 'admin.quote-requests', 'quotes.view', 'fa-file-magnifying-glass', 'Quote Requests', 0, false],
        ['Insurance', 'admin.orders', 'orders.view', 'fa-bag-shopping', 'Orders', $failedOrders, true],
        ['Customers', 'admin.leads', 'leads.view', 'fa-headset', 'Leads', $newLeads, false],
        ['System', 'admin.api-logs', 'api_logs.view', 'fa-heart-pulse', 'API Logs', 0, false],
    ];
    $area = null;
@endphp
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="robots" content="noindex, nofollow" />
    <title>@yield('title', 'Admin Console') — Delta Insurance</title>
    <link rel="icon" href="{{ asset('images/brand/delta-mark.png') }}" type="image/png" />
    <link rel="stylesheet" href="{{ asset('assets/css/fontawesome.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/css/delta-app.css') }}" />
    <script>
      /* Insurer logo fallback (same as DELTA.util.logoFallback). Defined in <head> so it exists before an <img> can fail. */
      window.DELTA = { util: { logoFallback: function (img) {
        var m = { din: ["DI", "#0B5E8E"], awnic: ["AW", "#7A5C2E"] }[img.getAttribute("data-pid")] || ["?", "#256383"], w = img.parentNode;
        if (!w) return; w.className = w.className.replace("ins-logo", "mono-d") + " is-fallback"; w.style.setProperty("--tint", m[1]); w.textContent = m[0];
      } } };
    </script>
  </head>
  <body class="d-app" data-app="admin">
    <div class="d-shell">
      <aside class="d-side" id="side">
        <a class="brand" href="{{ route('home') }}"><img src="{{ asset('images/brand/delta-logo-horizontal-white.svg') }}" alt="Delta Insurance" /></a>
        <div style="font-size:11.5px;color:#7fa3bd;padding:0 10px">Admin Console</div>
        <nav>@foreach ($nav as [$a, $route, $perm, $icon, $label, $count, $alert])@continue($perm && ! $user->can($perm))@if ($a !== $area)<div class="area">{{ $a }}</div>@php($area = $a)@endif<a href="{{ route($route) }}" class="{{ request()->routeIs($route, $route.'.*') ? 'active' : '' }}"><i class="fal {{ $icon }}"></i><span>{{ $label }}</span><span class="cnt {{ $count ? '' : 'd-none' }} {{ $alert ? 'alert' : '' }}">{{ $count }}</span></a>@endforeach</nav>
        <div class="me"><span class="av">{{ $user->initials() }}</span><div style="min-width:0"><b>{{ $user->name }}</b><span style="color:#7fa3bd">{{ $user->roleName() }}</span></div><form method="post" action="{{ route('admin.logout') }}" style="margin-left:auto">@csrf<button class="btn-d sm ghost" style="color:#cfe0ec;border-color:rgba(255,255,255,.2)" title="Sign out"><i class="far fa-arrow-right-from-bracket"></i></button></form></div>
      </aside>
      <div class="d-main">
        <div class="d-top">
          <button class="btn-d sm menu-btn" onclick="document.getElementById('side').classList.toggle('open')" aria-label="Menu"><i class="far fa-bars"></i></button>
          <div><div class="crumb">Admin Console</div><h1 id="pageTitle">@yield('title', 'Dashboard')</h1></div>
          <div class="right">@if (! app()->isProduction())<span class="demo-ribbon"><i class="fas fa-flask"></i> {{ strtoupper(config('app.env')) }}</span>@endif<a class="btn-d sm ghost" href="{{ route('home') }}"><i class="fal fa-globe"></i> Website</a></div>
        </div>
        <div class="d-view" id="view">
@yield('content')
        </div>
      </div>
    </div>
@hasSection('drawer')
    <div class="drawer open" id="drawer"><a class="bg" href="@yield('drawer_close')" aria-label="Close"></a><div class="pn" id="drawerPn">@yield('drawer')</div></div>
@endif
@if (session('toast'))
    <div class="toast-d" id="toast">{{ session('toast') }}</div>
    <script>setTimeout(function () { var t = document.getElementById("toast"); t && t.remove(); }, 4200);</script>
@endif
    <script>
      /* Close the drawer with Escape; table rows link to their detail. */
      document.addEventListener("keydown", function (e) { var c = document.querySelector("#drawer .bg"); if (e.key === "Escape" && c) location.href = c.href; });
      document.addEventListener("click", function (e) { var r = e.target.closest("tr[data-href]"); if (r && !e.target.closest("a,button,select,input,form")) location.href = r.getAttribute("data-href"); });
      /* Auto-submit switches and selects marked data-autosubmit. */
      document.addEventListener("change", function (e) { if (e.target.matches("[data-autosubmit]")) e.target.form.submit(); });
    </script>
  </body>
</html>
