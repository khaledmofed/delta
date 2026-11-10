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

/** Alert to the Delta team inbox (config site.leads_email). Reply goes straight to the customer. */
class NewLeadAlert extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public Lead $lead) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "New lead — {$this->lead->productName()} — {$this->lead->name} ({$this->lead->reference})",
            replyTo: $this->lead->email ? [new Address($this->lead->email, $this->lead->name)] : [],
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.new-lead', text: 'emails.new-lead-text');
    }
}
