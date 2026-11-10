<?php

namespace App\Mail\Journey;

use App\Models\QuoteRequest;
use App\Support\JourneyLinks;

/** To the customer right after the motor wizard: request received, link to the offers. */
class QuoteRequestReceived extends JourneyMail
{
    public function __construct(public QuoteRequest $qr)
    {
        parent::__construct();
    }

    protected function subjectLine(): string
    {
        return "Your motor insurance quote request — {$this->qr->reference}";
    }

    protected function template(): string
    {
        return 'quote-received';
    }

    protected function data(): array
    {
        return ['in' => $this->qr->input, 'resultsUrl' => JourneyLinks::quote($this->qr), 'days' => JourneyLinks::QUOTE_DAYS];
    }
}
