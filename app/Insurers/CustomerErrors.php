<?php

namespace App\Insurers;

use App\Insurers\Din\DinErrors;

/**
 * Turns an insurer error code into text that is safe to show a customer.
 * Technical messages (stack traces, raw insurer text) never reach the page.
 */
final class CustomerErrors
{
    public const GENERIC = 'This insurer could not give an online quote right now. Your other offers are not affected.';

    public const TIMEOUT = 'No response from this insurer. Your other offers are not affected.';

    public static function quote(string $insurer, ?string $code): string
    {
        return match ($insurer) {
            'din' => DinErrors::forCustomer($code) ?? self::GENERIC,
            default => self::GENERIC,
        };
    }

    /** Whether the error is about the customer's data (eligibility) rather than a technical failure. */
    public static function isEligibility(string $insurer, ?string $code): bool
    {
        return $insurer === 'din' && DinErrors::forCustomer($code) !== null;
    }
}
