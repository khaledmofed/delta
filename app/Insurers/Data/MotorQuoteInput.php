<?php

namespace App\Insurers\Data;

/**
 * Insurer-agnostic motor quote input. Dates are ISO (Y-m-d). Code values use the
 * Dubai Insurance code lists (nationality, emirate, plate source/code, NCD, traffic
 * transaction type, bank) — other insurers translate them in their mapper.
 */
final class MotorQuoteInput
{
    /** @param list<DocumentFile> $documents */
    public function __construct(
        public readonly string $nameEn,
        public readonly ?string $nameAr,
        public readonly string $email,
        public readonly string $mobile,          // 9715XXXXXXXX
        public readonly string $dob,
        public readonly string $gender,          // M | F
        public readonly string $nationality,
        public readonly string $emirate,
        public readonly string $emiratesId,      // 784XXXXXXXXXXXX
        public readonly string $emiratesIdExpiry,
        public readonly string $licenseNo,
        public readonly string $licenseFrom,
        public readonly string $licenseTo,
        public readonly int $ncdYears,           // 0..3 (3 = 3+)
        public readonly string $chassis,
        public readonly string $tcfNumber,
        public readonly string $plateSource,
        public readonly string $plateCode,
        public readonly string $plateNumber,
        public readonly string $registrationDate,
        public readonly string $trafficTranType,
        public readonly bool $isNew,
        public readonly bool $isGcc,
        public readonly string $coverType,       // comprehensive | third_party
        public readonly ?string $repair,         // agency | garage | null (any)
        public readonly ?string $financeBank,
        public readonly string $policyStart,
        public readonly ?string $make = null,
        public readonly ?string $model = null,
        public readonly ?int $year = null,
        public readonly ?float $vehicleValue = null,
        public readonly array $documents = [],
        /** Insurer-specific extras, e.g. ['awnic' => ['makeCode' => ..., 'modelCode' => ..., 'specDesc' => ...]] */
        public readonly array $extra = [],
    ) {}

    public static function fromArray(array $a): self
    {
        $docs = array_map(fn ($d) => $d instanceof DocumentFile ? $d : DocumentFile::fromArray($d), $a['documents'] ?? []);

        return new self(
            nameEn: trim($a['name_en']),
            nameAr: $a['name_ar'] ?? null,
            email: trim($a['email']),
            mobile: self::mobile($a['mobile']),
            dob: $a['dob'],
            gender: strtoupper($a['gender']),
            nationality: (string) $a['nationality'],
            emirate: (string) $a['emirate'],
            emiratesId: preg_replace('/\D/', '', $a['emirates_id']),
            emiratesIdExpiry: $a['emirates_id_expiry'],
            licenseNo: trim($a['license_no']),
            licenseFrom: $a['license_from'],
            licenseTo: $a['license_to'],
            ncdYears: (int) $a['ncd_years'],
            chassis: strtoupper(trim($a['chassis'])),
            tcfNumber: trim($a['tcf_number']),
            plateSource: (string) $a['plate_source'],
            plateCode: (string) $a['plate_code'],
            plateNumber: trim($a['plate_number']),
            registrationDate: $a['registration_date'],
            trafficTranType: (string) $a['traffic_tran_type'],
            isNew: (bool) ($a['is_new'] ?? false),
            isGcc: (bool) ($a['is_gcc'] ?? true),
            coverType: $a['cover_type'] ?? 'comprehensive',
            repair: ($a['repair'] ?? null) ?: null,
            financeBank: ($a['finance_bank'] ?? null) ?: null,
            policyStart: $a['policy_start'],
            make: $a['make'] ?? null,
            model: $a['model'] ?? null,
            year: isset($a['year']) ? (int) $a['year'] : null,
            vehicleValue: isset($a['vehicle_value']) ? (float) $a['vehicle_value'] : null,
            documents: $docs,
            extra: $a['extra'] ?? [],
        );
    }

    /** Normalise UAE mobile numbers to 9715XXXXXXXX. */
    public static function mobile(string $raw): string
    {
        $d = preg_replace('/\D/', '', $raw);
        $d = preg_replace('/^(00)?971/', '', $d);
        $d = ltrim($d, '0');

        return '971'.$d;
    }

    public function age(?string $on = null): int
    {
        return (new \DateTimeImmutable($this->dob))->diff(new \DateTimeImmutable($on ?? 'today'))->y;
    }
}
