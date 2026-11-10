<?php

namespace App\Mail\Journey;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Base for motor journey emails: queued, sent only after the database transaction commits. */
abstract class JourneyMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct()
    {
        $this->afterCommit();
    }

    abstract protected function subjectLine(): string;

    abstract protected function template(): string;

    /** Extra view data (links are built when the email is rendered). */
    protected function data(): array
    {
        return [];
    }

    /** Customer emails: replies go to Delta, not to no-reply. */
    protected function replyToAddress(): array
    {
        return [new Address(config('site.email'), 'Delta Insurance Services')];
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->subjectLine(), replyTo: $this->replyToAddress());
    }

    public function content(): Content
    {
        return new Content(view: "emails.journey.{$this->template()}", text: "emails.journey.{$this->template()}-text", with: $this->data());
    }
}
