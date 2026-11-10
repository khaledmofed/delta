<?php

namespace App\Insurers\Din;

/** DIN error codes that the customer journey must explain (spec V0.0.3 §4). */
final class DinErrors
{
    /** Customer-facing explanation; null = technical error (show generic message, alert ops). */
    public const CUSTOMER = [
        '8010' => 'This vehicle already has an active policy.',
        '8013' => 'Your Emirates ID has expired.',
        '8014' => 'Online cover is available for drivers aged 25 or over.',
        '8015' => 'Online cover is available for drivers under 65.',
        '8016' => 'Your driving licence has expired.',
        '8017' => 'Vehicles older than 10 years cannot be insured online.',
        '8023' => 'Electric vehicles cannot be insured online with this insurer.',
        '8024' => 'Non-GCC vehicles cannot be insured online with this insurer.',
        '8025' => 'The vehicle brand was not found.',
        '8026' => 'Your driving licence must have been issued at least one year ago.',
        '8027' => 'We could not complete the verification. An advisor will contact you.',
        '8033' => 'A bank LPO is required for financed vehicles.',
    ];

    public static function forCustomer(?string $code): ?string
    {
        return $code ? (self::CUSTOMER[$code] ?? null) : null;
    }
}
