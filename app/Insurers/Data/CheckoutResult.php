<?php

namespace App\Insurers\Data;

final class CheckoutResult
{
    public function __construct(
        public readonly bool $ok,
        public readonly ?string $paymentUrl = null,
        public readonly ?string $insurerQuoteNo = null,
        public readonly ?float $premium = null,
        public readonly ?float $vat = null,
        public readonly ?float $total = null,
        public readonly ?float $excess = null,
        public readonly ?string $errorCode = null,
        public readonly ?string $errorMessage = null,
        public readonly array $raw = [],
    ) {}
}
