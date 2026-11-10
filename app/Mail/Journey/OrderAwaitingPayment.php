<?php

namespace App\Mail\Journey;

use App\Models\Order;
use App\Support\JourneyLinks;

/** To the customer when the order is confirmed and sent to the insurer payment page (before payment). */
class OrderAwaitingPayment extends JourneyMail
{
    public function __construct(public Order $order)
    {
        parent::__construct();
    }

    protected function subjectLine(): string
    {
        return "Your order {$this->order->reference} — complete your payment";
    }

    protected function template(): string
    {
        return 'order-awaiting-payment';
    }

    protected function data(): array
    {
        return [
            'in' => $this->order->quoteRequest?->input ?? [],
            'insurer' => config("insurers.providers.{$this->order->insurer}.name", strtoupper($this->order->insurer)),
            'payUrl' => JourneyLinks::order($this->order, 'payment'),
            'trackUrl' => JourneyLinks::track($this->order),
        ];
    }
}
