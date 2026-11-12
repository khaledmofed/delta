<?php

namespace App\Support;

use App\Models\Order;
use App\Models\QuoteRequest;
use Illuminate\Support\Collection;

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

    /** Opening an order page makes it the order the header shows. */
    public static function viewing(Order $order): Order
    {
        static::order($order);
        session()->put('motor.current_order', $order->reference);

        return $order;
    }

    public static function currentOrder(): ?Order
    {
        $ref = session('motor.current_order');

        return $ref && in_array($ref, session('motor.orders', []), true) ? Order::where('reference', $ref)->first() : null;
    }

    /**
     * Orders opened in this browser session (header "My order" menu): the current one first, then newest first.
     *
     * @return Collection<int, Order>
     */
    public static function orders(): Collection
    {
        $refs = array_values(array_unique(session('motor.orders', [])));
        if (! $refs) {
            return collect();
        }
        $current = session('motor.current_order');

        return Order::whereIn('reference', $refs)->latest('id')->get()
            ->sortBy(fn (Order $o) => $o->reference === $current ? 0 : 1, SORT_REGULAR)->values();
    }

    /** "Exit": this browser no longer has access to the orders (or to the quotes behind them). */
    public static function forgetOrders(): void
    {
        session()->forget(['motor.orders', 'motor.current_order', 'motor.quote_requests']);
    }
}
