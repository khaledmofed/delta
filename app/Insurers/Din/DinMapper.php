<?php

namespace App\Insurers\Din;

use App\Insurers\Data\CheckoutResult;
use App\Insurers\Data\MotorQuoteInput;
use App\Insurers\Data\Offer;
use App\Insurers\Data\PolicyStatus;

/**
 * Pure request/response mapping for the Dubai Insurance (IIRIS) Motor Policy API V0.0.3.
 * No framework dependencies — unit-testable on its own.
 */
final class DinMapper
{
    public const VAT_RATE = 0.05;

    /** Delta document type => DIN document code / name */
    public const DOCUMENTS = [
        'eid_front' => ['103', 'Emirate ID Front'],
        'eid_back' => ['105', 'Emirate ID Back'],
        'mulkiya_front' => ['101', 'Mulkiya ID Front'],
        'mulkiya_back' => ['102', 'Mulkiya ID Back'],
        'licence_front' => ['106', 'Driving License Front'],
        'licence_back' => ['121', 'Driving License Back'],
        'bank_lpo' => ['107', 'BankLpo'],
    ];

    public static function date(string $iso): string
    {
        return (new \DateTimeImmutable($iso))->format('d/m/Y');
    }

    /** POST /api/v1/Insurance/GenerateQuote body */
    public static function quotePayload(MotorQuoteInput $in, bool $agencyRepair): array
    {
        $p = [
            'insuredName' => ['en' => $in->nameEn, 'ar' => $in->nameAr ?: $in->nameEn],
            'nationality' => $in->nationality,
            'nationalId' => $in->emiratesId,
            'idExpiryDt' => self::date($in->emiratesIdExpiry),
            'dateOfBirth' => self::date($in->dob),
            'gender' => $in->gender,
            'emirate' => $in->emirate,
            'emailAddress' => $in->email,
            'mobileNumber' => $in->mobile,
            'licenseNo' => $in->licenseNo,
            'licenseFmDt' => self::date($in->licenseFrom),
            'licenseToDt' => self::date($in->licenseTo),
            'chassisNumber' => $in->chassis,
            'regNumber' => $in->plateNumber,
            'regDt' => self::date($in->registrationDate),
            'plateCode' => $in->plateCode,
            'plateSource' => $in->plateSource,
            'tcfNumber' => $in->tcfNumber,
            'ncdYears' => (string) min(3, max(0, $in->ncdYears)),
            'trafficTranType' => $in->trafficTranType,
            'isVehBrandNew' => $in->isNew ? 'Y' : 'N',
            'agencyRepairYn' => $agencyRepair ? 'Y' : 'N',
        ];
        if ($in->financeBank) {
            $p['bankName'] = $in->financeBank;
        }
        $p['documentLists'] = [];
        foreach ($in->documents as $doc) {
            if (! isset(self::DOCUMENTS[$doc->type])) {
                continue;
            }
            [$code, $name] = self::DOCUMENTS[$doc->type];
            $p['documentLists'][] = ['code' => $code, 'name' => $name, 'base64' => $doc->base64(), 'type' => $doc->extension()];
        }

        return $p;
    }

    /** Which GenerateQuote calls to make: agency and/or garage. */
    public static function repairVariants(MotorQuoteInput $in): array
    {
        if ($in->coverType === 'third_party') {
            return [false];
        }

        return match ($in->repair) {
            'agency' => [true],
            'garage' => [false],
            default => [false, true],
        };
    }

    /** @return list<Offer> from a successful GenerateQuote response */
    public static function offers(array $json, bool $agencyRepair, string $requestId, ?string $coverType = null): array
    {
        $offers = [];
        foreach ($json['data'] ?? [] as $prod) {
            $name = self::text($prod['prodName'] ?? $prod['prodCode']);
            $mandatory = $prod['covers']['mandatory'] ?? [];
            $optional = $prod['covers']['optional'] ?? [];
            $isTpl = ((float) ($prod['sumInsured'] ?? 0)) <= 0 || stripos($name, 'third party') !== false;
            $type = $isTpl ? 'third_party' : 'comprehensive';
            if ($coverType && $type !== $coverType) {
                continue;
            }
            $covers = [];
            $premium = 0.0;
            foreach ($mandatory as $c) {
                $premium += (float) $c['premium'];
                $covers[] = ['code' => (string) $c['coverCode'], 'name' => self::text($c['coverName']), 'premium' => (float) $c['premium'], 'type' => 'mandatory'];
            }
            foreach ($optional ?? [] as $c) {
                $covers[] = ['code' => (string) $c['coverCode'], 'name' => self::text($c['coverName']), 'premium' => (float) $c['premium'], 'type' => 'optional'];
            }
            $vat = round($premium * self::VAT_RATE, 2);
            $offers[] = new Offer(
                insurer: 'din',
                planCode: (string) $prod['prodCode'],
                planName: preg_replace('/^\d+\s*-\s*/', '', $name).($isTpl ? '' : ($agencyRepair ? ' (Agency)' : ' (Garage)')),
                coverType: $type,
                repairType: $isTpl ? null : ($agencyRepair ? 'agency' : 'garage'),
                sumInsured: $isTpl ? null : (float) $prod['sumInsured'],
                premium: round($premium, 2),
                vat: $vat,
                total: round($premium + $vat, 2),
                deductible: null,   // DIN returns the excess only after ChooseScheme
                covers: $covers,
                insurerRef: null,
                indicative: true,
                raw: ['request_id' => $requestId, 'agency' => $agencyRepair],
            );
        }

        return $offers;
    }

    /** POST /api/v1/Insurance/ChooseScheme body */
    public static function chooseSchemePayload(Offer $offer, array $optionalCovers): array
    {
        $mandatory = array_column(array_filter($offer->covers, fn ($c) => $c['type'] === 'mandatory'), 'code');
        $allowed = array_column(array_filter($offer->covers, fn ($c) => $c['type'] === 'optional'), 'code');
        $optional = array_values(array_intersect($optionalCovers, $allowed));

        return [
            'prodCode' => $offer->planCode,
            'covers' => ['mandatory' => implode(',', $mandatory), 'optional' => implode(',', $optional)],
        ];
    }

    public static function checkout(array $json): CheckoutResult
    {
        if (! self::isOk($json) || empty($json['data']['paymentUrl'])) {
            [$code, $msg] = self::error($json);

            return new CheckoutResult(false, errorCode: $code, errorMessage: $msg, raw: $json);
        }
        $d = $json['data'];

        return new CheckoutResult(
            ok: true,
            paymentUrl: $d['paymentUrl'],
            insurerQuoteNo: $d['quotationNo'] ?? null,
            premium: (float) ($d['netPremium'] ?? $d['grossPremium'] ?? 0),
            vat: (float) ($d['vat'] ?? 0),
            total: (float) ($d['netToCustomer'] ?? 0),
            excess: isset($d['excess']) && $d['excess'] !== '' ? (float) $d['excess'] : null,
            raw: $d,
        );
    }

    public static function policy(array $json): PolicyStatus
    {
        $code = (string) ($json['statusId'] ?? '');
        $d = $json['data'] ?? [];
        if (self::isOk($json) && ($d['paymentStatus'] ?? null) === 'S' && strtoupper($d['polStatus'] ?? '') === 'APPROVED') {
            return new PolicyStatus(
                status: PolicyStatus::ISSUED,
                policyNo: $d['polNo'] ?? null,
                paymentStatus: 'S',
                total: isset($d['payable']) ? (float) $d['payable'] : null,
                documents: ! empty($d['documents']) ? ['policy_schedule.html' => $d['documents']] : [],
                raw: array_diff_key($d, ['documents' => 1]),
            );
        }
        [$c, $m] = self::error($json);
        // 8203 = payment not completed yet; 8202 = no quote for request id
        $status = in_array($code, ['8203', '8022'], true) || (self::isOk($json) && ($d['paymentStatus'] ?? null) !== 'F') ? PolicyStatus::PENDING : PolicyStatus::FAILED;

        return new PolicyStatus($status, paymentStatus: $d['paymentStatus'] ?? null, errorCode: $c, errorMessage: $m, raw: is_array($d) ? $d : []);
    }

    public static function isOk(array $json): bool
    {
        return (int) ($json['status'] ?? 0) === 1;
    }

    /** @return array{0:?string,1:?string} */
    public static function error(array $json): array
    {
        $code = isset($json['statusId']) ? (string) $json['statusId'] : null;
        $msg = self::text($json['message'] ?? null);
        if (is_string($json['data'] ?? null) && $json['data'] !== '') {
            $msg .= ' ['.$json['data'].']';   // e.g. missing parameter names on 8032
        }

        return [$code, $msg ?: null];
    }

    public static function text(mixed $v): string
    {
        if (is_array($v)) {
            return (string) ($v['en'] ?? reset($v) ?? '');
        }

        return (string) $v;
    }
}
