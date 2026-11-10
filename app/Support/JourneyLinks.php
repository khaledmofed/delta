<?php

namespace App\Support;

use App\Models\Order;
use App\Models\QuoteRequest;
use Illuminate\Support\Facades\URL;

/**
 * Links in customer emails. Journey pages are tied to the browser session (JourneyAccess), so an email
 * link is a temporary signed URL that opens the session for that quote request / order on any device.
 */
final class JourneyLinks
{
    public const QUOTE_DAYS = 7;     // offers are valid 7 days

    public const ORDER_DAYS = 30;

    /** @param 'confirmation'|'payment'|'document' $to */
    public static function order(Order $order, string $to = 'confirmation'): string
    {
        return URL::temporarySignedRoute('motor.order.open', now()->addDays(self::ORDER_DAYS), ['order' => $order, 'to' => $to]);
    }

    public static function quote(QuoteRequest $qr): string
    {
        return URL::temporarySignedRoute('motor.quote.open', now()->addDays(self::QUOTE_DAYS), ['quoteRequest' => $qr]);
    }

    public static function track(?Order $order = null): string
    {
        return route('track', $order ? ['ref' => $order->reference] : []);
    }
}
