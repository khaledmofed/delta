<?php

namespace App\Insurers\Qic;

use App\Insurers\Data\CheckoutResult;
use App\Insurers\Data\MotorQuoteInput;
use App\Insurers\Data\Offer;
use App\Insurers\Data\PolicyStatus;

/**
 * Pure request/response mapping for QIC (Qatar Insurance – UAE) Motor API by Anoud Technologies
 * ("Anoud APIs for Motor Insurance" spec + Delta's Postman collection, received 24-Nov-2026).
 *
 * Flow: motor/tariff (quote + schemes) -> motor/netPremium (final premium for the chosen scheme)
 *       -> sendPayLink (QIC payment link) -> customer pays -> getLeadPolList (policy no.) -> getPolicyReport (PDF).
 *
 * QIC uses its own master codes (make/model, vehicle type, cylinders, nationality, registration location,
 * Autodata trim id "admeId"). Delta's input carries DIN codes, so nationality / emirate are translated with a
 * translator (type, dinCode) => qicCode, and the vehicle codes come in $input->extra['qic'] (from Bayanaty
 * getQicCodes or the QIC master data). Missing mappings raise MappingMissing — nothing is guessed.
 *
 * Several points are not settled by the spec (success codes, VAT, TPL/agency naming, payment status):
 * see "QIC — open questions" in CONTEXT.md. They are isolated here so the answers change one place.
 */
final class QicMapper
{
    public const VAT_RATE = 0.05;

    /** QIC "Private" usage (spec default) and "UAE only" geographic area. */
    public const USAGE_PRIVATE = '1001';

    public const GEO_UAE = '1001';

    public static function date(string $iso): string
    {
        return (new \DateTimeImmutable($iso))->format('d/m/Y');
    }

    /**
     * POST motor/tariff body.
     *
     * @param  callable(string $type, string $dinCode):?string  $translate
     */
    public static function tariffPayload(MotorQuoteInput $in, callable $translate, ?string $today = null): array
    {
        $v = $in->extra['qic'] ?? [];
        $tpl = $in->coverType === 'third_party';
        $required = ['makeCode', 'modelCode', 'vehicleType', 'noOfCylinder', 'seatingCapacity'];
        if (! $tpl) {
            $required[] = 'admeId';   // Autodata trim id: mandatory for comprehensive
        }
        foreach ($required as $k) {
            if (! isset($v[$k]) || $v[$k] === '') {
                throw new MappingMissing("QIC vehicle field {$k} is required (choose the vehicle from QIC / Bayanaty codes).");
            }
        }
        $sumInsured = $tpl ? 0.0 : (float) ($v['sumInsured'] ?? $in->vehicleValue ?? 0);
        if (! $tpl && $sumInsured <= 0) {
            throw new MappingMissing('QIC comprehensive quote needs the vehicle value (sumInsured).');
        }
        $regDate = new \DateTimeImmutable($in->registrationDate);

        return array_filter([
            'insuredName' => mb_substr($in->nameEn, 0, 60),
            'makeCode' => (string) $v['makeCode'],
            'modelCode' => (string) $v['modelCode'],
            'modelYear' => (int) ($in->year ?? $regDate->format('Y')),
            'sumInsured' => $sumInsured,
            'vehicleType' => (string) $v['vehicleType'],
            'vehicleUsage' => (string) ($v['vehicleUsage'] ?? self::USAGE_PRIVATE),
            'noOfCylinder' => (string) $v['noOfCylinder'],
            'seatingCapacity' => (int) $v['seatingCapacity'],
            'regYear' => (int) $regDate->format('Y'),
            'gccSpec' => $in->isGcc ? '1' : '0',
            'previousInsuranceValid' => (int) ($v['previousInsuranceValid'] ?? ($in->isNew ? 0 : 1)),
            'totalLoss' => (int) ($v['totalLoss'] ?? 0),
            'driverDOB' => self::date($in->dob),
            'insuredAge' => $in->age($today),
            'noClaimYear' => (string) min(6, max(0, $in->ncdYears)),
            'chassisNo' => $in->chassis,
            'driverExp' => isset($v['driverExp']) ? (int) $v['driverExp'] : self::years($in->licenseFrom, $today),
            'policyFromDate' => (new \DateTimeImmutable($in->policyStart))->format('Y-m-d').'T00:00:00.000Z',
            'civilId' => self::eid($in->emiratesId),
            'mobileNo' => $in->mobile,
            'emailId' => mb_substr($in->email, 0, 25) === $in->email ? $in->email : null,   // spec max length 25
            'tcfNo' => $in->tcfNumber,
            'engineNo' => $v['engineNo'] ?? null,
            'colorCode' => $v['colorCode'] ?? null,
            'occupation' => $v['occupation'] ?? null,
            'city' => $v['city'] ?? null,
            'financedStatus' => $in->financeBank ? '1' : '0',
            'financeBank' => $in->financeBank ? self::need($translate, 'bank', $in->financeBank) : null,
            'geoArea' => self::GEO_UAE,
            'regnLocation' => self::need($translate, 'emirate', $in->emirate),
            'nationality' => self::need($translate, 'nationality', $in->nationality),
            'admeId' => $tpl ? ($v['admeId'] ?? null) : (string) $v['admeId'],
            'firstRegDate' => self::date($in->registrationDate),
            'promoCode' => $v['promoCode'] ?? null,
        ], fn ($x) => $x !== null && $x !== '');
    }

    /**
     * motor/tariff response -> offers. One offer per scheme; netPremium is treated as VAT-inclusive
     * (the netPremium API says "Tax included in the Net Premium") and is indicative until motor/netPremium.
     *
     * @return list<Offer>
     */
    public static function offers(array $json, ?float $sumInsured, string $requestId, ?string $coverType = null, ?string $repair = null): array
    {
        $out = [];
        foreach ($json['schemes'] ?? [] as $s) {
            $name = trim((string) ($s['schemeName'] ?? ''));
            $isTpl = (bool) preg_match('/third\s*party|\bTPL\b|\bTP\b/i', $name);
            $type = $isTpl ? 'third_party' : 'comprehensive';
            if ($coverType && $type !== $coverType) {
                continue;
            }
            $repairType = $isTpl ? null : (preg_match('/\bagency\b/i', $name) && ! preg_match('/non[\s-]*agency/i', $name) ? 'agency' : 'garage');
            if ($repair && $repairType && $repairType !== $repair) {
                continue;
            }
            $total = round((float) ($s['netPremium'] ?? 0), 2);
            $vat = round($total - $total / (1 + self::VAT_RATE), 2);
            $covers = [];
            foreach (['basicCovers' => 'mandatory', 'inclusiveCovers' => 'mandatory', 'optionalCovers' => 'optional'] as $key => $kind) {
                foreach ($s[$key] ?? [] as $c) {
                    $covers[] = ['code' => (string) ($c['code'] ?? ''), 'name' => (string) ($c['name'] ?? ''), 'premium' => (float) ($c['premium'] ?? 0), 'type' => $kind];
                }
            }
            $deductible = null;
            foreach ($s['excessCovers'] ?? [] as $c) {
                if (preg_match('/excess|deductible/i', (string) ($c['name'] ?? ''))) {
                    $deductible = (float) ($c['premium'] ?? 0);
                    break;
                }
            }
            $out[] = new Offer(
                insurer: 'qic',
                planCode: ($s['productCode'] ?? '').':'.($s['schemeCode'] ?? ''),
                planName: $name ?: 'QIC Motor',
                coverType: $type,
                repairType: $repairType,
                sumInsured: $isTpl ? null : $sumInsured,
                premium: round($total - $vat, 2),
                vat: $vat,
                total: $total,
                deductible: $deductible,
                covers: $covers,
                insurerRef: isset($json['quoteNo']) ? (string) $json['quoteNo'] : null,
                indicative: true,   // final price comes from motor/netPremium at checkout
                raw: ['request_id' => $requestId, 'productCode' => $s['productCode'] ?? null, 'schemeCode' => $s['schemeCode'] ?? null,
                    'excess' => $s['excessCovers'] ?? [], 'discounts' => $s['discountCovers'] ?? []],
            );
        }

        return $out;
    }

    /** POST motor/netPremium body (shape of Delta's Postman collection; optional covers per the spec). */
    public static function netPremiumPayload(Offer $offer, array $optionalCovers): array
    {
        [$product, $scheme] = array_pad(explode(':', $offer->planCode, 2), 2, '');
        $chosen = array_values(array_intersect($optionalCovers, array_column(array_filter($offer->covers, fn ($c) => $c['type'] === 'optional'), 'code')));
        $entry = ['schemeCode' => $scheme, 'productCode' => $product];
        if ($chosen) {
            $entry['optionalCovers'] = array_map(fn ($c) => ['coverCode' => $c], $chosen);
        }

        return ['quoteNo' => (string) $offer->insurerRef, 'schemes' => [$entry]];
    }

    /** POST sendPayLink body. */
    public static function payLinkPayload(string $quoteNo, MotorQuoteInput $in, array $cfg): array
    {
        return ['quoteNo' => $quoteNo, 'userId' => (string) ($cfg['pay_user_id'] ?? ''), 'emailId' => $in->email, 'userType' => (string) ($cfg['user_type'] ?? 'A')];
    }

    /** netPremium + sendPayLink responses -> checkout result. */
    public static function checkout(string $quoteNo, array $premium, array $payLink): CheckoutResult
    {
        if (! self::isOk($premium, 'netPremium')) {
            [$c, $m] = self::error($premium);

            return new CheckoutResult(false, errorCode: $c, errorMessage: $m, raw: ['netPremium' => $premium]);
        }
        if (! self::isOk($payLink, 'payLink')) {
            [$c, $m] = self::error($payLink);

            return new CheckoutResult(false, insurerQuoteNo: $quoteNo, errorCode: $c, errorMessage: $m, raw: ['netPremium' => $premium, 'sendPayLink' => $payLink]);
        }
        $total = round((float) $premium['netPremium'], 2);
        $vat = isset($premium['taxAmount']) ? round((float) $premium['taxAmount'], 2) : round($total - $total / (1 + self::VAT_RATE), 2);

        return new CheckoutResult(
            ok: true,
            paymentUrl: (string) $payLink['payLink'],
            insurerQuoteNo: (string) ($premium['quoteNo'] ?? $quoteNo),
            premium: round($total - $vat, 2),
            vat: $vat,
            total: $total,
            raw: ['netPremium' => $premium, 'sendPayLink' => array_diff_key($payLink, ['payLink' => 1])],
        );
    }

    /** POST getLeadPolList body: look the policy up by chassis (falls back to mobile). */
    public static function policyLookupPayload(array $context): array
    {
        return array_filter([
            'chassisNo' => $context['chassis'] ?? null,
            'mobileNo' => empty($context['chassis']) ? ($context['mobile'] ?? null) : null,
        ]);
    }

    /**
     * getLeadPolList response -> the policy issued for our quote, if any. QIC has no payment-status API in the spec,
     * so "no policy yet" stays PENDING (never FAILED): a payment can only be marked failed by an explicit answer.
     *
     * @return array{0: PolicyStatus, 1: ?array} status + the matching policy row
     */
    public static function policy(array $json, string $quoteNo): array
    {
        $rows = $json['policyList'] ?? $json['policies'] ?? $json['data'] ?? (isset($json['policyNo']) ? [$json] : []);
        $rows = is_array($rows) && array_is_list($rows) ? $rows : [];
        foreach ($rows as $r) {
            if ((string) ($r['quoteNo'] ?? '') === $quoteNo && ! empty($r['policyNo']) && strtoupper((string) ($r['activeYn'] ?? 'Y')) === 'Y') {
                return [new PolicyStatus(PolicyStatus::ISSUED, policyNo: (string) $r['policyNo'], paymentStatus: 'S',
                    total: isset($r['premium']) && is_numeric($r['premium']) ? (float) $r['premium'] : null, raw: self::safeRow($r)), $r];
            }
        }
        [$c, $m] = self::error($json);

        return [new PolicyStatus(PolicyStatus::PENDING, errorCode: self::isOk($json) ? null : $c, errorMessage: self::isOk($json) ? null : $m, raw: ['rows' => count($rows)]), null];
    }

    /**
     * Success = no error message and the expected field present. QIC's respCode values are not listed in the spec
     * (open question), so they are not relied on.
     */
    public static function isOk(array $json, ?string $expect = null): bool
    {
        if ($json === [] || trim((string) ($json['errMessage'] ?? '')) !== '') {
            return false;
        }

        return $expect === null || (isset($json[$expect]) && $json[$expect] !== '' && $json[$expect] !== []);
    }

    public static function error(array $json): array
    {
        $code = isset($json['respCode']) ? (string) $json['respCode'] : null;
        $msg = trim((string) ($json['errMessage'] ?? $json['message'] ?? $json['error'] ?? ''));

        return [$code, $msg !== '' ? $msg : 'QIC request failed'];
    }

    /** Emirates ID as 784-YYYY-NNNNNNN-C (the format in QIC's sample). */
    public static function eid(string $digits): string
    {
        $d = preg_replace('/\D/', '', $digits);

        return strlen($d) === 15 ? substr($d, 0, 3).'-'.substr($d, 3, 4).'-'.substr($d, 7, 7).'-'.substr($d, 14, 1) : $d;
    }

    private static function years(string $from, ?string $today): int
    {
        return min(11, (new \DateTimeImmutable($from))->diff(new \DateTimeImmutable($today ?? 'today'))->y);
    }

    /** Policy rows carry personal data; keep only what the order needs. */
    private static function safeRow(array $r): array
    {
        return array_intersect_key($r, array_flip(['policyNo', 'quoteNo', 'polStartDate', 'polEndDate', 'activeYn', 'premium', 'prodDesc', 'lobDesc']));
    }

    private static function need(callable $translate, string $type, string $dinCode): string
    {
        $v = $translate($type, $dinCode);
        if ($v === null || $v === '') {
            throw new MappingMissing("No QIC {$type} code mapped for Delta/DIN code {$dinCode}.");
        }

        return (string) $v;
    }
}
