<?php

namespace App\Insurers\Awnic;

use App\Insurers\Data\CheckoutResult;
use App\Insurers\Data\MotorQuoteInput;
use App\Insurers\Data\Offer;
use App\Insurers\Data\PolicyStatus;

/**
 * Pure request/response mapping for the Al Wathba (AWNIC) Insure API — Motor Aggregators (UAT Postman collection).
 * AWNIC uses its own master codes (make/model/year/trim, nationality, ...). Delta's input carries DIN codes, so
 * a translator (type, dinCode) => awnicCode is injected; missing mappings raise MappingMissing.
 */
final class AwnicMapper
{
    /** Delta document type => AWNIC docCategory (TO CONFIRM with AWNIC — only 1001 appears in the collection). */
    public const DOC_CATEGORY = [
        'eid_front' => '1001', 'eid_back' => '1001',
        'licence_front' => '1001', 'licence_back' => '1001',
        'mulkiya_front' => '1001', 'mulkiya_back' => '1001', 'bank_lpo' => '1001',
    ];

    public static function date(string $iso): string
    {
        return strtoupper((new \DateTimeImmutable($iso))->format('d-M-Y'));
    }

    /** @param callable(string $type, string $dinCode):?string $translate */
    public static function premiumPayload(MotorQuoteInput $in, callable $translate): array
    {
        $v = $in->extra['awnic'] ?? [];
        foreach (['makeCode', 'modelCode', 'specDesc'] as $k) {
            if (empty($v[$k])) {
                throw new MappingMissing("AWNIC vehicle field {$k} is required (choose the vehicle from AWNIC masters).");
            }
        }

        return [
            'makeCode' => $v['makeCode'],
            'modelCode' => $v['modelCode'],
            'specDesc' => $v['specDesc'],
            'sumInsured' => (float) ($v['sumInsured'] ?? $in->vehicleValue ?? 0),
            'mfgYear' => (string) ($in->year ?? ''),
            'isBrandNewYN' => $in->isNew ? 'Y' : 'N',
            'noClaimYear' => min(3, max(0, $in->ncdYears)),
            'insurerType' => 'I',
            'dob' => self::date($in->dob),
            'licenseDate' => self::date($in->licenseFrom),
            'nationality' => self::need($translate, 'nationality', $in->nationality),
            'usageType' => '101',
            'registeredDate' => self::date($in->registrationDate),
            'registeredLocation' => self::need($translate, 'emirate', $in->emirate),
            'isGccSpecYN' => $in->isGcc ? 'Y' : 'N',
            'tcnNo' => $in->tcfNumber,
            'chassisNo' => $in->chassis,
            'isExistAWNICCustomerYN' => 'N',
            'covers' => [],
        ];
    }

    /** @return list<Offer> */
    public static function offers(array $json, ?float $sumInsured, string $requestId, ?string $coverType = null, ?string $repair = null): array
    {
        $out = [];
        foreach ($json['premiumInfo'] ?? [] as $p) {
            $name = (string) ($p['planName'] ?? '');
            $isTpl = stripos($name, 'third party') !== false || stripos($name, 'TPL') !== false;
            $type = $isTpl ? 'third_party' : 'comprehensive';
            if ($coverType && $type !== $coverType) {
                continue;
            }
            $agency = ! $isTpl && stripos($name, 'non agency') === false && stripos($name, 'agency') !== false;
            $repairType = $isTpl ? null : ($agency ? 'agency' : 'garage');
            if ($repair && $repairType && $repairType !== $repair) {
                continue;
            }
            $premium = (float) ($p['finalPremiumAmt'] ?? $p['premiumAmt'] ?? 0);
            $vat = (float) ($p['finalPremiumVat'] ?? round($premium * 0.05, 2));
            $covers = array_map(fn ($c) => [
                'code' => (string) ($c['coverId'] ?? $c['coverCode'] ?? ''),
                'name' => (string) ($c['coverDesc'] ?? ''),
                'premium' => (float) ($c['premium'] ?? 0),
                'type' => (($c['optedYN'] ?? 'Y') === 'Y') ? 'mandatory' : 'optional',
            ], $p['covers'] ?? []);
            $out[] = new Offer(
                insurer: 'awnic',
                planCode: $name,
                planName: $name,
                coverType: $type,
                repairType: $repairType,
                sumInsured: $isTpl ? null : $sumInsured,
                premium: $premium,
                vat: $vat,
                total: round($premium + $vat, 2),
                deductible: isset($p['deductibleAmt']) ? (float) $p['deductibleAmt'] : null,
                covers: $covers,
                insurerRef: $json['quoteNo'] ?? $p['quoteNo'] ?? null,
                indicative: false,
                raw: ['request_id' => $requestId, 'plan' => array_diff_key($p, ['covers' => 1])],
            );
        }

        return $out;
    }

    /** POST motor/initializePolicyGeneration body. @param callable(string,string):?string $translate */
    public static function initializePayload(MotorQuoteInput $in, Offer $offer, array $optionalCovers, string $partnerQuoteNo, array $cfg, callable $translate): array
    {
        $v = $in->extra['awnic'] ?? [];
        $parts = preg_split('/\s+/', trim($in->nameEn));
        $last = count($parts) > 1 ? array_pop($parts) : $parts[0];
        $start = new \DateTimeImmutable($in->policyStart);
        $covers = array_values(array_unique(array_merge(
            array_column(array_filter($offer->covers, fn ($c) => $c['type'] === 'mandatory'), 'code'),
            array_values(array_intersect($optionalCovers, array_column($offer->covers, 'code'))),
        )));

        return [
            'motorInsuredInfo' => [
                'address' => $v['address'] ?? 'UAE',
                'contactNo' => '0'.substr($in->mobile, 3),
                'customerType' => 'Individual',
                'dob' => self::date($in->dob),
                'emailId' => $in->email,
                'emiratesId' => $in->emiratesId,
                'firstName' => implode(' ', $parts),
                'insurerType' => 'I',
                'isExistAWNICCustomerYN' => 'N',
                'lastName' => $last,
                'licenseDate' => self::date($in->licenseFrom),
                'licenseNo' => $in->licenseNo,
                'nationality' => self::need($translate, 'nationality', $in->nationality),
                'occupation' => $v['occupation'] ?? '',
                'trnNo' => '',
            ],
            'motorPolicyInfo' => [
                'assuredCode' => (int) $cfg['customer_code'],
                'brokerCode' => (int) $cfg['customer_code'],
                'channel' => 'BROKER',
                'customerCode' => (int) $cfg['customer_code'],
                'paymentMode' => 'Online',
                'planName' => $offer->planName,
                'policyStartDate' => self::date($start->format('Y-m-d')),
                'policyEndDate' => self::date($start->modify('+1 year -1 day')->format('Y-m-d')),
                'productCode' => $cfg['product_code'],
                'quoteNo' => $partnerQuoteNo,
                'quoteProcessBy' => $cfg['quote_process_by'],
                'paymentSuccessURL' => $cfg['success_url'],
                'paymentFailURL' => $cfg['fail_url'],
            ],
            'motorPremiumInfo' => [
                'currency' => '001',
                'currencyRate' => '1',
                'grossPremium' => $offer->premium,
                'vatAmount' => (string) $offer->vat,
            ],
            'motorVehicleInfo' => [
                'bodyType' => (string) ($v['bodyType'] ?? ''),
                'chassisNo' => $in->chassis,
                'color' => (string) ($v['color'] ?? ''),
                'companyCode' => '001',
                'engineNo' => (string) ($v['engineNo'] ?? ''),
                'homeCountryLicenseEffDate' => self::date($in->licenseFrom),
                'isAgencyYN' => $offer->repairType === 'agency' ? 'Y' : 'N',
                'isBrandNewYN' => $in->isNew ? 'Y' : 'N',
                'isGccSpecYN' => $in->isGcc ? 'Y' : 'N',
                'isMortgageYN' => $in->financeBank ? 'Y' : 'N',
                'isTransactionLicenseYN' => 'Y',
                'makeCode' => $v['makeCode'] ?? '',
                'mfgYear' => (string) ($in->year ?? ''),
                'mileage' => (string) ($v['mileage'] ?? ''),
                'modelCode' => $v['modelCode'] ?? '',
                'mortgageBank' => $in->financeBank ? (string) ($translate('bank', $in->financeBank) ?? '') : '',
                'noClaimYear' => (string) min(3, max(0, $in->ncdYears)),
                'noOfCylinderCode' => (string) ($v['noOfCylinderCode'] ?? ''),
                'plateCategory' => (string) ($v['plateCategory'] ?? ''),
                'plateCode' => (string) ($translate('plateCode', $in->plateCode) ?? $in->plateCode),
                'plateNo' => $in->plateNumber,
                'registeredDate' => self::date($in->registrationDate),
                'registeredLocation' => self::need($translate, 'emirate', $in->emirate),
                'seatingCapacity' => (string) ($v['seatingCapacity'] ?? ''),
                'specCode' => $v['specCode'] ?? $v['specDesc'] ?? '',
                'specDesc' => $v['specDesc'] ?? '',
                'sumInsured' => (string) ($offer->sumInsured ?? ''),
                'tcnNo' => $in->tcfNumber,
                'transactionType' => (string) ($v['transactionType'] ?? 'Vehicle Renewal'),
                'usageType' => '101',
                'covers' => array_map(fn ($c) => ['coverId' => $c], $covers),
            ],
        ];
    }

    public static function checkout(array $json): CheckoutResult
    {
        if (! self::isOk($json)) {
            [$c, $m] = self::error($json);

            return new CheckoutResult(false, errorCode: $c, errorMessage: $m, raw: $json);
        }
        $info = $json['motorPolicyInfo'] ?? [];

        return new CheckoutResult(
            ok: true,
            paymentUrl: $json['paymentUrl'] ?? $info['paymentUrl'] ?? null,
            insurerQuoteNo: $json['awnicQuoteNo'] ?? $info['awnicQuoteNo'] ?? $info['quoteNo'] ?? null,
            raw: $json,
        );
    }

    public static function policy(array $json): PolicyStatus
    {
        if (! self::isOk($json)) {
            [$c, $m] = self::error($json);

            return new PolicyStatus(PolicyStatus::FAILED, errorCode: $c, errorMessage: $m, raw: $json);
        }
        $i = $json['motorPolicyInfo'] ?? [];
        $docs = array_filter([
            'policy' => $i['policyDocId'] ?? null,
            'tax_invoice' => $i['taxInvoiceDocId'] ?? null,
            'bank_letter' => $i['bankLetterDocId'] ?? null,
            'debit_note' => $i['debitNoteDocId'] ?? null,
            'credit_note' => $i['creditNoteDocId'] ?? null,
        ]);

        return new PolicyStatus(! empty($i['policyNo']) ? PolicyStatus::ISSUED : PolicyStatus::PENDING, policyNo: $i['policyNo'] ?? null, documents: $docs, raw: $i);
    }

    public static function isOk(array $json): bool
    {
        return strtoupper((string) ($json['isSuccess'] ?? 'N')) === 'Y';
    }

    public static function error(array $json): array
    {
        $e = $json['errorList'][0] ?? [];

        return [$e['code'] ?? null, $e['desc'] ?? ($json['message'] ?? 'AWNIC request failed')];
    }

    private static function need(callable $translate, string $type, string $dinCode): string
    {
        $v = $translate($type, $dinCode);
        if ($v === null || $v === '') {
            throw new MappingMissing("No AWNIC {$type} code mapped for Delta/DIN code {$dinCode}.");
        }

        return (string) $v;
    }
}
