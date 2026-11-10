<?php

namespace App\Mail\Journey;

use App\Models\Order;
use App\Support\JourneyLinks;

/** To the customer when the insurer confirms the policy, with the link to download the policy document. */
class PolicyIssued extends JourneyMail
{
    public function __construct(public Order $order)
    {
        parent::__construct();
    }

    protected function subjectLine(): string
    {
        return "You’re insured — policy {$this->order->policy?->policy_no}";
    }

    protected function template(): string
    {
        return 'policy-issued';
    }

    protected function data(): array
    {
        $hasDocument = $this->order->documents()->where('type', 'policy_schedule')->exists();

        return [
            'policy' => $this->order->policy,
            'in' => $this->order->quoteRequest?->input ?? [],
            'insurer' => config("insurers.providers.{$this->order->insurer}.name", strtoupper($this->order->insurer)),
            'documentUrl' => $hasDocument ? JourneyLinks::order($this->order, 'document') : null,
            'policyUrl' => JourneyLinks::order($this->order),
            'trackUrl' => JourneyLinks::track($this->order),
            'days' => JourneyLinks::ORDER_DAYS,
        ];
    }
}
