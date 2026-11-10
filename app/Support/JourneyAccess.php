<?php

namespace App\Support;

use App\Models\Order;
use App\Models\QuoteRequest;

/**
 * Customers have no account yet, so quote requests and orders are tied to the browser session
 * that created them. Knowing a reference is not enough to open someone else's quote.
 */
final class JourneyAccess
{
    public static function grantQuote(QuoteRequest $qr): void
    {
        session()->push('motor.quote_requests', $qr->reference);
    }

    public static function grantOrder(Order $order): void
    {
        session()->push('motor.orders', $order->reference);
        session()->put('motor.current_order', $order->reference);
    }

    public static function quote(QuoteRequest $qr): QuoteRequest
    {
        abort_unless(in_array($qr->reference, session('motor.quote_requests', []), true), 404);

        return $qr;
    }

    public static function order(Order $order): Order
    {
        abort_unless(in_array($order->reference, session('motor.orders', []), true), 404);

        return $order;
    }

    public static function currentOrder(): ?Order
    {
        $ref = session('motor.current_order');

        return $ref && in_array($ref, session('motor.orders', []), true) ? Order::where('reference', $ref)->first() : null;
    }
}
