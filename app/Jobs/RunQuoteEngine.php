<?php

namespace App\Jobs;

use App\Models\QuoteRequest;
use App\Services\MotorQuotes;
use App\Services\QuoteEngine;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/** Asks every enabled insurer for a quote. The results page polls until all insurers have answered. */
class RunQuoteEngine implements ShouldQueue, ShouldBeUnique
{
    use Queueable;

    /** Insurers are not retried automatically: a second GenerateQuote would re-send documents. */
    public int $tries = 1;

    /**
     * Insurer timeouts are 30 s each; leave room for two insurers and the DIN agency/garage variants.
     * Must stay below the queue's retry_after (REDIS_QUEUE_RETRY_AFTER) or the job is handed out twice.
     */
    public int $timeout = 170;

    public function __construct(public int $quoteRequestId) {}

    public function uniqueId(): string
    {
        return (string) $this->quoteRequestId;
    }

    public function handle(QuoteEngine $engine, MotorQuotes $quotes): void
    {
        $qr = QuoteRequest::find($this->quoteRequestId);
        // Only a fresh request: a duplicate delivery must never send the customer's documents twice.
        if (! $qr || $qr->status !== 'QUOTE_REQUESTED') {
            return;
        }
        $engine->run($qr, $quotes->input($qr));
    }

    public function failed(?\Throwable $e): void
    {
        if ($qr = QuoteRequest::find($this->quoteRequestId)) {
            app(QuoteEngine::class)->abandon($qr, 'job failed: '.($e ? class_basename($e).': '.$e->getMessage() : 'unknown'));
        }
    }
}
