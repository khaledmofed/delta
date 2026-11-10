{{-- Staff sign-in. No admin login screen exists in the prototype; built from delta-app.css components. --}}
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="robots" content="noindex, nofollow" />
    <title>Staff sign in — Delta Insurance</title>
    <link rel="icon" href="{{ asset('images/brand/delta-mark.png') }}" type="image/png" />
    <link rel="stylesheet" href="{{ asset('assets/css/fontawesome.css') }}" />
    <link rel="stylesheet" href="{{ asset('assets/css/delta-app.css') }}" />
  </head>
  <body class="d-app" style="min-height:100vh;display:grid;place-items:center;padding:24px 16px">
    <div style="width:min(420px,100%)">
      <div class="card-d welcome" style="padding:26px 24px;margin-bottom:-14px;border-radius:16px 16px 0 0">
        <img src="{{ asset('images/brand/delta-logo-horizontal-white.svg') }}" alt="Delta Insurance" style="height:36px" />
        <h2 class="mt-3" style="font-size:22px">Admin Console</h2>
        <p style="margin:4px 0 0;color:#a9c4d8">Delta Insurance staff only</p>
      </div>
      <form class="card-d" method="post" action="{{ route('admin.login.submit') }}" style="position:relative;border-radius:0 0 16px 16px;padding:28px 24px 24px">
        @csrf
        @if ($errors->any())
          <div class="note-d warn" style="margin-bottom:16px"><i class="fal fa-circle-exclamation"></i><div>{{ $errors->first() }}</div></div>
        @endif
        <label style="display:block;margin-bottom:14px"><span class="lbl">Email</span><input class="inp" style="width:100%" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username" /></label>
        <label style="display:block;margin-bottom:14px"><span class="lbl">Password</span><input class="inp" style="width:100%" type="password" name="password" required autocomplete="current-password" /></label>
        <label class="row-flex" style="margin-bottom:18px;font-size:13.5px"><input type="checkbox" name="remember" value="1" /> Keep me signed in on this device</label>
        <button class="btn-d pri" style="width:100%;justify-content:center" type="submit">Sign in <i class="far fa-arrow-right"></i></button>
        <p class="muted mt-3" style="font-size:12.5px;margin-bottom:0"><i class="fal fa-shield-check"></i> Sign-ins are recorded in the audit log.</p>
      </form>
    </div>
  </body>
</html>
