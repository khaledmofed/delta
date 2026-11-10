<?php

namespace App\Services;

use App\Mail\Journey\OrderAwaitingPayment;
use App\Mail\Journey\PolicyIssued;
use App\Mail\Journey\QuoteRequestReceived;
use App\Mail\Journey\TeamOrderAlert;
use App\Mail\Journey\TeamPolicyIssued;
use App\Models\Order;
use App\Models\QuoteRequest;
use Illuminate\Contracts\Mail\Mailable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Motor journey emails (all queued). A mail problem is logged and never interrupts the journey:
 * the customer can always use "Track my order" and staff see everything in the admin panel.
 */
class JourneyMailer
{
    public function quoteReceived(QuoteRequest $qr): void
    {
        $this->send($qr->input['email'] ?? null, $qr->input['name_en'] ?? null, new QuoteRequestReceived($qr), $qr->reference);
    }

    /** Sent once, when the customer is first sent to the insurer payment page. */
    public function awaitingPayment(Order $order): void
    {
        $this->send($order->customer?->email, $order->customer?->name, new OrderAwaitingPayment($order), $order->reference);
    }

    public function policyIssued(Order $order): void
    {
        $this->send($order->customer?->email, $order->customer?->name, new PolicyIssued($order), $order->reference);
        $this->send(config('site.ops_email'), 'Delta operations', new TeamPolicyIssued($order), $order->reference);
    }

    /** Internal alert: checkout refused by the insurer, payment declined, policy not issued... */
    public function failure(Order $order, string $what, ?string $detail = null): void
    {
        $this->send(config('site.ops_email'), 'Delta operations', new TeamOrderAlert($order, $what, $detail), $order->reference);
    }

    protected function send(?string $email, ?string $name, Mailable $mail, string $ref): void
    {
        if (! $email) {
            Log::warning('Journey email skipped: no address', ['ref' => $ref, 'mail' => class_basename($mail)]);

            return;
        }
        try {
            Mail::to($email, $name)->queue($mail);
        } catch (\Throwable $e) {
            Log::error('Journey email could not be queued', ['ref' => $ref, 'mail' => class_basename($mail), 'e' => $e->getMessage()]);
        }
    }
}
