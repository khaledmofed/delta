<?php

namespace App\Support;

use App\Insurers\CustomerErrors;
use App\Models\Quote;
use App\Models\QuoteRequest;
use App\Services\MotorQuotes;
use App\Services\QuoteEngine;
use Illuminate\Support\Carbon;

/** View data for the motor journey pages. Same formats as DELTA.util in delta-store.js. */
final class MotorPresenter
{
    /** An insurer still "pending" after this long is shown as not responding and the page stops waiting. */
    public const WAIT_SECONDS = 180;

    public static function money(float|string|null $v): string
    {
        return 'AED '.number_format((float) $v, 2);
    }

    public static function money0(float|string|null $v): string
    {
        return 'AED '.number_format(round((float) $v));
    }

    public static function date(mixed $d): string
    {
        return $d ? Carbon::parse($d)->format('d M Y') : '—';
    }

    public static function insurer(string $code): array
    {
        return [
            'code' => $code,
            'name' => config("insurers.providers.{$code}.name", strtoupper($code)),
            'logo' => asset("images/insurers/{$code}.png"),
        ];
    }

    /** State of a quote request for the results page and its polling endpoint. */
    public static function results(QuoteRequest $qr): array
    {
        $in = $qr->input;
        $expired = $qr->created_at && $qr->created_at->diffInSeconds(now()) > self::WAIT_SECONDS;
        $insurers = [];
        foreach ($qr->insurer_results ?: [] as $code => $r) {
            $status = $r['status'] ?? QuoteEngine::PENDING;
            if ($status === QuoteEngine::PENDING && $expired) {
                $status = 'error';
                $r['message'] = CustomerErrors::TIMEOUT;
            }
            $insurers[] = self::insurer($code) + [
                'status' => $status === 'success' ? 'success' : ($status === QuoteEngine::PENDING ? 'pending' : 'error'),
                'offers' => (int) ($r['offers'] ?? 0),
                'ms' => (int) ($r['ms'] ?? 0),
                'message' => $status === 'success' ? null : ($r['message'] ?? CustomerErrors::GENERIC),
                'errorCode' => $r['error_code'] ?? null,
            ];
        }

        return [
            'reference' => $qr->reference,
            'done' => ! collect($insurers)->contains('status', 'pending'),
            'vehicle' => trim(($in['year'] ?? '').' '.($in['make'] ?? '').' '.($in['model'] ?? '')),
            'make' => $in['make'] ?? '',
            'model' => $in['model'] ?? '',
            'driver' => $in['name_en'] ?? '',
            'starts' => self::date($in['policy_start'] ?? null),
            'cover' => ['type' => $in['cover_type'] ?? 'comprehensive', 'repair' => $in['repair'] ?? null],
            'insurers' => $insurers,
            'quotes' => $qr->quotes()->orderBy('total')->get()->map(fn (Quote $q) => self::quote($q))->all(),
            'statusUrl' => route('motor.status', $qr),
            'compareUrl' => route('motor.compare', $qr),
        ];
    }

    public static function quote(Quote $q): array
    {
        return [
            'id' => $q->id,
            'insurer' => $q->insurer,
            'insurerName' => config("insurers.providers.{$q->insurer}.name", strtoupper($q->insurer)),
            'logo' => asset("images/insurers/{$q->insurer}.png"),
            'planName' => $q->plan_name,
            'insuranceType' => $q->cover_type,
            'repairType' => $q->repair_type,
            'sumInsured' => $q->sum_insured !== null ? (float) $q->sum_insured : null,
            'premium' => (float) $q->premium,
            'vat' => (float) $q->vat,
            'total' => (float) $q->total,
            'deductible' => $q->deductible !== null ? (float) $q->deductible : null,
            'covers' => array_map(fn ($c) => [
                'name' => (string) $c['name'], 'premium' => (float) $c['premium'], 'type' => $c['type'],
            ], $q->covers ?? []),
            'indicative' => (bool) ($q->raw['indicative'] ?? false),
            'validUntil' => self::date($q->valid_until),
            'ref' => $q->insurer_ref ?: substr((string) ($q->raw['request_id'] ?? ''), 0, 8),
            'selectUrl' => route('motor.select', $q),
        ];
    }

    /** Vehicle, plate and document summary shown on checkout ("captured" box). */
    public static function captured(QuoteRequest $qr): array
    {
        $in = $qr->input;
        $eid = $in['emirates_id'] ?? '';

        return [
            'vehicle' => trim(($in['year'] ?? '').' '.($in['make'] ?? '').' '.($in['model'] ?? '')),
            'chassis' => $in['chassis'] ?? '',
            'plate' => trim(MotorQuotes::label('plateSource', $in['plate_source'] ?? null).' '.MotorQuotes::label('plateCode', $in['plate_code'] ?? null).' '.($in['plate_number'] ?? '')),
            'tcf' => $in['tcf_number'] ?? '',
            // Masked: the full Emirates ID is not shown again.
            'emiratesId' => strlen($eid) === 15 ? substr($eid, 0, 3).'-'.substr($eid, 3, 4).'-•••••••-'.substr($eid, -1) : '',
            'licence' => $in['license_no'] ?? '',
            'documents' => $qr->documents()->orderBy('id')->get()->map(fn ($d) => [
                'label' => MotorQuotes::DOCUMENTS[$d->type]['label'] ?? $d->type,
                'file' => $d->original_name,
            ])->all(),
        ];
    }
}
