<?php

namespace App\Mail;

use App\Models\Lead;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Confirmation to the customer: we received your request, reference, what happens next. */
class LeadReceived extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public Lead $lead) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "We received your request — {$this->lead->reference}",
            replyTo: [new Address(config('site.email'), 'Delta Insurance Services')],
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.lead-received', text: 'emails.lead-received-text');
    }
}
