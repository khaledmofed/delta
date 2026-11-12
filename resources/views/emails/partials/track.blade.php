{{-- Tracking line for customer order emails. $order, $trackUrl (JourneyLinks::track, order number pre-filled). --}}
<table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="margin:0 0 20px;border-collapse:collapse">
  <tr><td style="background:#f5f8fb;border:1px solid #e3e9ef;border-radius:12px;padding:12px 16px;font-size:13.5px;line-height:1.6;color:#4a5a6b">
    Your order number is <b style="color:#122a43">{{ $order->reference }}</b>. Track your order any time on
    <a href="{{ $trackUrl }}" style="color:#256383;font-weight:bold">Track my order</a> with this number and your email or mobile.
  </td></tr>
</table>
