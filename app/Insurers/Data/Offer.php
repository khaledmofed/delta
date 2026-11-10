<?php

namespace App\Insurers\Data;

/** One plan returned by an insurer, normalised for comparison. */
final class Offer
{
    /** @param list<array{code:string,name:string,premium:float,type:string}> $covers */
    public function __construct(
        public readonly string $insurer,
        public readonly string $planCode,
        public readonly string $planName,
        public readonly string $coverType,
        public readonly ?string $repairType,
        public readonly ?float $sumInsured,
        public readonly float $premium,
        public readonly float $vat,
        public readonly float $total,
        public readonly ?float $deductible,
        public readonly array $covers = [],
        public readonly ?string $insurerRef = null,
        public readonly bool $indicative = false,   // true when the final price comes at checkout (DIN)
        public readonly array $raw = [],
    ) {}

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
