<?php

namespace App\Insurers\Data;

final class QuoteResult
{
    /** @param list<Offer> $offers */
    public function __construct(
        public readonly string $insurer,
        public readonly bool $ok,
        public readonly array $offers = [],
        public readonly ?string $errorCode = null,
        public readonly ?string $errorMessage = null,
        public readonly ?string $requestId = null,
        public readonly array $context = [],
    ) {}

    public static function fail(string $insurer, ?string $code, ?string $message, ?string $requestId = null): self
    {
        return new self($insurer, false, [], $code, $message, $requestId);
    }
}
