<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>@yield('title', 'Delta Insurance Services')</title>
    <style>
        :root { --navy:#122a43; --steel:#256383; --mint:#00ff97; --bg:#f5f8fb; --ink:#14212e; --muted:#6b7a8a; }
        * { box-sizing: border-box; }
        body { margin:0; font-family: system-ui, -apple-system, "Segoe UI", sans-serif; background: var(--bg); color: var(--ink); }
        .wrap { max-width: 880px; margin: 0 auto; padding: 48px 16px; }
        .card { background:#fff; border-radius: 20px; padding: 32px; box-shadow: 0 10px 30px rgba(18,42,67,.06); }
        h1 { margin: 0 0 8px; color: var(--navy); }
        p { color: var(--muted); line-height: 1.6; }
        .badge { display:inline-block; padding:4px 10px; border-radius: 999px; background:#e7fff3; color:#00894f; font-size: 13px; font-weight:600; }
    </style>
</head>
<body>
    <div class="wrap">@yield('content')</div>
</body>
</html>
