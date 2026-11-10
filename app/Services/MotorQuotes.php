<?php

namespace App\Services;

use App\Http\Requests\MotorQuoteRequest;
use App\Insurers\Data\MotorQuoteInput;
use App\Insurers\Din\DinMapper;
use App\Models\Customer;
use App\Models\Document;
use App\Models\InsurerCode;
use App\Models\QuoteRequest;
use App\Models\Vehicle;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/** Motor quote requests: from the wizard form to a stored QuoteRequest, and back to a MotorQuoteInput. */
class MotorQuotes
{
    /** Private disk for customer documents — never "public". */
    public const DISK = 'local';

    /** Same list and order as DOCUMENT_TYPES in delta-data.js. */
    public const DOCUMENTS = [
        'eid_front' => ['label' => 'Emirates ID — front', 'financed_only' => false],
        'eid_back' => ['label' => 'Emirates ID — back', 'financed_only' => false],
        'licence_front' => ['label' => 'Driving licence — front', 'financed_only' => false],
        'licence_back' => ['label' => 'Driving licence — back', 'financed_only' => false],
        'mulkiya_front' => ['label' => 'Mulkiya — front', 'financed_only' => false],
        'mulkiya_back' => ['label' => 'Mulkiya — back', 'financed_only' => false],
        'bank_lpo' => ['label' => 'Bank LPO', 'financed_only' => true],
    ];

    /** 1×1 PNG used for "Use sample files" in demo mode only. */
    protected const SAMPLE_PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==';

    public function __construct(protected QuoteEngine $engine) {}

    public function create(MotorQuoteRequest $request): QuoteRequest
    {
        $v = $request->validated();
        $input = $this->normalise($v, $request->financed());

        return DB::transaction(function () use ($request, $v, $input) {
            $customer = Customer::create([
                'name' => $input['name_en'], 'name_ar' => $input['name_ar'], 'email' => $input['email'], 'mobile' => $input['mobile'],
                'emirates_id' => $input['emirates_id'], 'emirates_id_expiry' => $input['emirates_id_expiry'], 'dob' => $input['dob'],
                'gender' => $input['gender'], 'nationality' => $input['nationality'], 'emirate' => $input['emirate'],
                'license_no' => $input['license_no'], 'license_from' => $input['license_from'], 'license_to' => $input['license_to'],
            ]);
            $vehicle = Vehicle::create([
                'customer_id' => $customer->id, 'chassis' => $input['chassis'], 'make' => $input['make'], 'model' => $input['model'],
                'year' => $input['year'], 'tcf_number' => $input['tcf_number'], 'plate_source' => $input['plate_source'],
                'plate_code' => $input['plate_code'], 'plate_number' => $input['plate_number'], 'registration_date' => $input['registration_date'],
                'is_new' => $input['is_new'], 'is_gcc' => $input['is_gcc'], 'finance_bank' => $input['finance_bank'],
            ]);
            $qr = QuoteRequest::create([
                'product' => 'motor',
                'customer_id' => $customer->id,
                'vehicle_id' => $vehicle->id,
                'input' => $input,
                'status' => 'QUOTE_REQUESTED',
                'insurer_results' => $this->engine->pendingResults('motor'),
            ]);

            foreach (self::DOCUMENTS as $type => $meta) {
                if ($meta['financed_only'] && ! $request->financed()) {
                    continue;
                }
                $file = $request->file("documents.{$type}");
                if ($file instanceof UploadedFile) {
                    $this->storeUpload($qr, $type, $file);
                } elseif ($request->usesSample($type) && config('site.demo')) {
                    $this->storeSample($qr, $type);
                }
            }

            return $qr;
        });
    }

    /** Wizard field names => MotorQuoteInput::fromArray keys (plus a few display-only extras). */
    public function normalise(array $v, bool $financed): array
    {
        $comprehensive = $v['coverType'] === 'comprehensive';

        return [
            'name_en' => trim($v['name']),
            'name_ar' => ($v['nameAr'] ?? null) ?: null,
            'email' => trim($v['email']),
            'mobile' => MotorQuoteInput::mobile($v['mobile']),
            'dob' => $v['dob'],
            'gender' => $v['gender'],
            'nationality' => (string) $v['nationality'],
            'emirate' => (string) $v['emirate'],
            'emirates_id' => $v['emiratesId'],
            'emirates_id_expiry' => $v['eidExpiry'],
            'license_no' => trim($v['licenseNo']),
            'license_from' => $v['licenseFrom'],
            'license_to' => $v['licenseTo'],
            'ncd_years' => (int) $v['ncdYears'],
            'chassis' => $v['chassis'],
            'tcf_number' => trim($v['tcfNumber']),
            'plate_source' => (string) $v['plateSource'],
            'plate_code' => (string) $v['plateCode'],
            'plate_number' => trim($v['regNumber']),
            'registration_date' => $v['regDt'],
            'traffic_tran_type' => (string) $v['trafficTranType'],
            'is_new' => ($v['isNew'] ?? 'no') === 'yes',
            'is_gcc' => ($v['gccSpec'] ?? 'yes') === 'yes',
            'is_electric' => ($v['ev'] ?? 'no') === 'yes',
            'cover_type' => $v['coverType'],
            'repair' => $comprehensive && in_array($v['repair'] ?? null, ['agency', 'garage'], true) ? $v['repair'] : null,
            'finance_bank' => $financed ? (string) $v['bank'] : null,
            'policy_start' => $v['policyStart'],
            'make' => $v['make'],
            'model' => $v['model'],
            'year' => (int) $v['year'],
            // Add-ons the customer asked for; matched to insurer optional covers at checkout.
            'addons' => array_keys(array_filter([
                'roadside' => $v['roadside'] ?? false, 'rentACar' => $v['rentACar'] ?? false,
                'pa' => $v['pa'] ?? false, 'gcc' => $v['gcc'] ?? false,
            ])),
        ];
    }

    public function input(QuoteRequest $qr): MotorQuoteInput
    {
        $docs = $qr->documents()->get()->map(fn (Document $d) => [
            'type' => $d->type,
            'file_name' => $d->original_name,
            'loader' => fn () => Storage::disk($d->disk)->get($d->path),
        ])->all();

        return MotorQuoteInput::fromArray(['documents' => $docs] + $qr->input);
    }

    /** Label for a Dubai Insurance code (nationality, plateSource, ...) for display. */
    public static function label(string $type, ?string $code): string
    {
        if ($code === null || $code === '') {
            return '';
        }

        return (string) (InsurerCode::where('insurer', 'din')->where('type', $type)->where('code', $code)->value('label') ?? $code);
    }

    protected function storeUpload(QuoteRequest $qr, string $type, UploadedFile $file): Document
    {
        $ext = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'bin');
        $path = $file->storeAs($this->folder($qr), $type.'-'.Str::random(20).'.'.$ext, self::DISK);

        return $this->record($qr, $type, $path, $file->getClientOriginalName(), $file->getMimeType() ?: 'application/octet-stream', $file->getSize());
    }

    protected function storeSample(QuoteRequest $qr, string $type): Document
    {
        $bytes = base64_decode(self::SAMPLE_PNG);
        $path = $this->folder($qr).'/'.$type.'-sample-'.Str::random(8).'.png';
        Storage::disk(self::DISK)->put($path, $bytes);

        return $this->record($qr, $type, $path, "{$type}_sample.png", 'image/png', strlen($bytes), 'sample');
    }

    protected function record(QuoteRequest $qr, string $type, string $path, string $name, string $mime, int $size, string $status = 'uploaded'): Document
    {
        return $qr->documents()->create([
            'type' => $type,
            'insurer_code' => DinMapper::DOCUMENTS[$type][0] ?? null,
            'disk' => self::DISK,
            'path' => $path,
            'original_name' => mb_substr($name, 0, 190),
            'mime' => mb_substr($mime, 0, 80),
            'size' => $size,
            'status' => $status,
        ]);
    }

    protected function folder(QuoteRequest $qr): string
    {
        return 'quote-requests/'.$qr->reference;
    }
}
