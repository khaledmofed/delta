<?php

namespace App\Mail\Journey;

use App\Models\Order;

/** Internal: a new policy was issued online. */
class TeamPolicyIssued extends JourneyMail
{
    public function __construct(public Order $order)
    {
        parent::__construct();
    }

    protected function subjectLine(): string
    {
        return "New policy {$this->order->policy?->policy_no} — {$this->insurer()} — {$this->order->reference}";
    }

    protected function template(): string
    {
        return 'team-policy-issued';
    }

    protected function replyToAddress(): array
    {
        return [];
    }

    protected function insurer(): string
    {
        return config("insurers.providers.{$this->order->insurer}.name", strtoupper($this->order->insurer));
    }

    protected function data(): array
    {
        return ['policy' => $this->order->policy, 'in' => $this->order->quoteRequest?->input ?? [], 'insurer' => $this->insurer(), 'adminUrl' => route('admin.orders.show', $this->order)];
    }
}
