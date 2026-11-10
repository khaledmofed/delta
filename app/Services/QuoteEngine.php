<?php

namespace App\Services;

use App\Insurers\Data\MotorQuoteInput;
use App\Insurers\Data\Offer;
use App\Insurers\InsurerManager;
use App\Models\Quote;
use App\Models\QuoteRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Asks every enabled insurer for the product and stores normalised quotes.
 * One insurer failing never blocks the others.
 */
class QuoteEngine
{
    public function __construct(protected InsurerManager $insurers) {}

    public function run(QuoteRequest $qr, MotorQuoteInput $input): QuoteRequest
    {
        $results = [];
        foreach ($this->insurers->forProduct($qr->product) as $code => $adapter) {
            $requestId = (string) Str::uuid();
            $start = microtime(true);
            try {
                $res = $adapter->quote($input, $requestId);
                foreach ($res->offers as $offer) {
                    $this->store($qr, $offer);
                }
                $results[$code] = [
                    'status' => $res->ok ? 'success' : 'error',
                    'offers' => count($res->offers),
                    'error_code' => $res->errorCode,
                    'error' => $res->errorMessage,
                    'request_id' => $requestId,
                ];
            } catch (\Throwable $e) {
                Log::error("Quote failed for {$code}", ['qr' => $qr->reference, 'e' => $e->getMessage()]);
                $results[$code] = ['status' => 'exception', 'offers' => 0, 'error' => $e->getMessage(), 'request_id' => $requestId];
            }
            $results[$code]['ms'] = (int) round((microtime(true) - $start) * 1000);
        }

        $qr->forceFill([
            'insurer_results' => $results,
            'status' => $qr->quotes()->exists() ? 'QUOTES_RECEIVED' : 'NO_QUOTES',
        ])->save();

        return $qr->fresh('quotes');
    }

    protected function store(QuoteRequest $qr, Offer $o): Quote
    {
        return $qr->quotes()->create([
            'insurer' => $o->insurer,
            'plan_code' => $o->planCode,
            'plan_name' => $o->planName,
            'cover_type' => $o->coverType,
            'repair_type' => $o->repairType,
            'sum_insured' => $o->sumInsured,
            'premium' => $o->premium,
            'vat' => $o->vat,
            'total' => $o->total,
            'deductible' => $o->deductible,
            'covers' => $o->covers,
            'insurer_ref' => $o->insurerRef,
            'raw' => $o->raw + ['indicative' => $o->indicative],
            'valid_until' => now()->addDays(7),
        ]);
    }

    public static function offerFromQuote(Quote $q): Offer
    {
        return new Offer($q->insurer, $q->plan_code, $q->plan_name, $q->cover_type, $q->repair_type,
            $q->sum_insured !== null ? (float) $q->sum_insured : null, (float) $q->premium, (float) $q->vat, (float) $q->total,
            $q->deductible !== null ? (float) $q->deductible : null, $q->covers ?? [], $q->insurer_ref, (bool) ($q->raw['indicative'] ?? false), $q->raw ?? []);
    }
}
