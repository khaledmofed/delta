<?php

namespace App\Insurers\Contracts;

use App\Insurers\Data\CheckoutResult;
use App\Insurers\Data\MotorQuoteInput;
use App\Insurers\Data\Offer;
use App\Insurers\Data\PolicyStatus;
use App\Insurers\Data\QuoteResult;

/**
 * Every insurer integration implements this contract. The rest of the app never
 * talks to an insurer API directly.
 *
 * Flow:  quote()  ->  checkout()  -> customer pays on the insurer page  ->  policyStatus()
 */
interface InsurerAdapter
{
    public function code(): string;

    /** Connectivity / credential check for `php artisan insurers:ping`. */
    public function ping(): array;

    /** $requestId is Delta's transaction id for this insurer (reused by the next steps). */
    public function quote(MotorQuoteInput $input, string $requestId): QuoteResult;

    /** @param list<string> $optionalCovers cover codes the customer added */
    public function checkout(MotorQuoteInput $input, Offer $offer, array $optionalCovers, string $requestId): CheckoutResult;

    public function policyStatus(string $requestId, array $context = []): PolicyStatus;
}
