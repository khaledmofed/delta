<?php

namespace App\Insurers\Data;

final class PolicyStatus
{
    public const PENDING = 'pending';
    public const ISSUED = 'issued';
    public const FAILED = 'failed';

    /** @param array<string,string> $documents name => base64 or insurer doc id */
    public function __construct(
        public readonly string $status,
        public readonly ?string $policyNo = null,
        public readonly ?string $paymentStatus = null,
        public readonly ?float $total = null,
        public readonly array $documents = [],
        public readonly ?string $errorCode = null,
        public readonly ?string $errorMessage = null,
        public readonly array $raw = [],
    ) {}
}
