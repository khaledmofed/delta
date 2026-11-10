{{-- Simple branded email layout (inline styles: email clients ignore stylesheets). Delta navy #122a43, steel #256383, mint #00FF97. --}}
<!doctype html>
<html lang="en">
<head><meta charset="utf-8" /><meta name="viewport" content="width=device-width, initial-scale=1" /><title>@yield('title')</title></head>
<body style="margin:0;padding:0;background:#f4f7fa;font-family:Arial,Helvetica,sans-serif;color:#0f1f30">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f7fa;padding:24px 12px">
    <tr><td align="center">
      <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;background:#ffffff;border-radius:14px;overflow:hidden;border:1px solid #e3e9ef">
        <tr><td style="background:#122a43;padding:22px 28px;border-bottom:3px solid #00FF97">
          <img src="{{ asset('images/brand/delta-logo-horizontal-white.png') }}" alt="Delta Insurance Services L.L.C" height="34" style="display:block;height:34px;border:0;color:#ffffff;font-size:18px;font-weight:bold" />
        </td></tr>
        <tr><td style="padding:28px;font-size:15px;line-height:1.6">
@yield('body')
        </td></tr>
        <tr><td style="padding:18px 28px;background:#f2f8fc;border-top:1px solid #e3e9ef;font-size:12.5px;color:#7a8a99;line-height:1.6">
          Delta Insurance Services L.L.C · {{ config('site.location') }} · Insurance brokers since 1990<br />
          <a href="tel:{{ config('site.phone_href') }}" style="color:#256383;text-decoration:none">{{ config('site.phone') }}</a> · <a href="mailto:{{ config('site.email') }}" style="color:#256383;text-decoration:none">{{ config('site.email') }}</a>
        </td></tr>
      </table>
    </td></tr>
  </table>
</body>
</html>
