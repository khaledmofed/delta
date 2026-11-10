<?php

namespace App\Mail\Journey;

use App\Models\Order;

/** Internal: something failed on an order (insurer refused checkout, payment declined...). */
class TeamOrderAlert extends JourneyMail
{
    public function __construct(public Order $order, public string $what, public ?string $detail = null)
    {
        parent::__construct();
    }

    protected function subjectLine(): string
    {
        return "⚠ {$this->what} — {$this->order->reference}";
    }

    protected function template(): string
    {
        return 'team-order-alert';
    }

    protected function replyToAddress(): array
    {
        return [];
    }

    protected function data(): array
    {
        return [
            'in' => $this->order->quoteRequest?->input ?? [],
            'insurer' => config("insurers.providers.{$this->order->insurer}.name", strtoupper($this->order->insurer)),
            'adminUrl' => route('admin.orders.show', $this->order),
        ];
    }
}
