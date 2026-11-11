<?php

namespace App\Http\Requests;

use App\Insurers\Din\DinErrors;
use App\Services\MotorQuotes;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

/**
 * Motor quote wizard (motor-quote.js). Same field rules and the same insurer eligibility
 * rules as the browser, so a customer who bypasses the JavaScript gets the same answer.
 * Code fields are checked against the Dubai Insurance code lists in insurer_codes.
 */
class MotorQuoteRequest extends FormRequest
{
    /** Wizard step for each field — used by the page to jump to the first error. */
    public const STEPS = [
        1 => ['name', 'nameAr', 'email', 'mobile', 'dob', 'gender', 'nationality', 'emirate', 'emiratesId', 'eidExpiry'],
        2 => ['licenseNo', 'ncdYears', 'licenseFrom', 'licenseTo'],
        3 => ['trafficTranType', 'isNew', 'chassis', 'tcfNumber', 'make', 'model', 'year', 'gccSpec', 'ev', 'plateSource', 'plateCode', 'regNumber', 'regDt', 'financed', 'bank'],
        4 => ['documents', 'samples', 'coverType', 'repair', 'roadside', 'rentACar', 'pa', 'gcc', 'policyStart'],
        5 => ['consentShare', 'consentTerms'],
    ];

    /** Eligibility rule => [wizard step, DIN error code]. Messages come from DinErrors. */
    public const ELIGIBILITY = [
        'age_min' => [1, '8014'], 'age_max' => [1, '8015'], 'eid_expired' => [1, '8013'],
        'licence_expired' => [2, '8016'], 'licence_new' => [2, '8026'],
        'old_vehicle_comprehensive' => [4, '8017'], 'non_gcc' => [3, '8024'], 'electric' => [3, '8023'],
    ];

    /** Vehicles older than this can only get Third Party Liability online. */
    public const MAX_AGE_COMPREHENSIVE = 10;

    public const OLD_VEHICLE_MESSAGE = 'Comprehensive cover is not available online for vehicles older than 10 years. Please choose Third Party Liability.';

    /** Traffic transaction types offered in the wizard. */
    public const TRAFFIC_TYPES = ['101', '102', '103', '104', '109'];

    protected array $eligibility = [];

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $digits = fn ($v) => preg_replace('/\D/', '', (string) $v);
        $this->merge([
            'mobile' => $digits($this->input('mobile')),
            'emiratesId' => $digits($this->input('emiratesId')),
            'chassis' => strtoupper(trim((string) $this->input('chassis'))),
            'samples' => array_values(array_filter((array) $this->input('samples', []))),
            // add-on checkboxes arrive as "on" from a plain form post
            'roadside' => $this->boolean('roadside'),
            'rentACar' => $this->boolean('rentACar'),
            'pa' => $this->boolean('pa'),
            'gcc' => $this->boolean('gcc'),
        ]);
    }

    public function rules(): array
    {
        $code = fn (string $type) => Rule::exists('insurer_codes', 'code')->where('insurer', 'din')->where('type', $type);
        $doc = ['file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'];
        $today = CarbonImmutable::today();
        $documentRules = [];
        foreach (MotorQuotes::DOCUMENTS as $type => $meta) {
            $required = $meta['financed_only'] ? $this->financed() : true;
            $documentRules["documents.{$type}"] = array_merge(
                [$required && ! $this->usesSample($type) ? 'required' : 'nullable'],
                $doc,
            );
        }

        return [
            'name' => ['required', 'string', 'max:120'],
            'nameAr' => ['nullable', 'string', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:150'],
            'mobile' => ['required', 'regex:/^5\d{8}$/'],
            'dob' => ['required', 'date', 'before:today'],
            'gender' => ['required', $code('gender')],
            'nationality' => ['required', $code('nationality')],
            'emirate' => ['required', $code('emirate')],
            'emiratesId' => ['required', 'regex:/^784\d{12}$/'],
            'eidExpiry' => ['required', 'date'],

            'licenseNo' => ['required', 'string', 'max:30'],
            'ncdYears' => ['required', $code('ncdYears')],
            'licenseFrom' => ['required', 'date', 'before_or_equal:today'],
            'licenseTo' => ['required', 'date', 'after:licenseFrom'],

            'trafficTranType' => ['required', Rule::in(self::TRAFFIC_TYPES), $code('trafficTranType')],
            'isNew' => ['nullable', Rule::in(['yes', 'no'])],
            'chassis' => ['required', 'regex:/^[A-Z0-9]{17}$/'],
            'tcfNumber' => ['required', 'string', 'max:30'],
            'make' => ['required', 'string', 'max:60'],
            'model' => ['required', 'string', 'max:60'],
            'year' => ['required', 'integer', 'min:1980', 'max:'.($today->year + 1)],
            'gccSpec' => ['nullable', Rule::in(['yes', 'no'])],
            'ev' => ['nullable', Rule::in(['yes', 'no'])],
            'plateSource' => ['required', $code('plateSource')],
            'plateCode' => ['required', $code('plateCode')],
            'regNumber' => ['required', 'string', 'max:20'],
            'regDt' => ['required', 'date', 'before_or_equal:today'],
            'financed' => ['nullable', Rule::in(['yes', 'no'])],
            'bank' => [Rule::requiredIf($this->financed()), 'nullable', $code('bank')],

            'documents' => ['nullable', 'array'],
            ...$documentRules,
            'samples' => ['array'],
            'samples.*' => [Rule::in(array_keys(MotorQuotes::DOCUMENTS)), function ($attr, $value, $fail) {
                if (! config('site.demo')) {
                    $fail('Please upload your own documents.');
                }
            }],
            'coverType' => ['required', Rule::in(['comprehensive', 'third_party'])],
            'repair' => ['nullable', Rule::in(['any', 'agency', 'garage'])],
            'roadside' => ['nullable', 'boolean'],
            'rentACar' => ['nullable', 'boolean'],
            'pa' => ['nullable', 'boolean'],
            'gcc' => ['nullable', 'boolean'],
            'policyStart' => ['required', 'date', 'after_or_equal:today', 'before_or_equal:'.$today->addDays(30)->toDateString()],

            'consentShare' => ['accepted'],
            'consentTerms' => ['accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'mobile.regex' => 'Please enter a UAE mobile number.',
            'emiratesId.regex' => 'Format: 784-XXXX-XXXXXXX-X',
            'chassis.regex' => 'VIN must be 17 characters.',
            'policyStart.*' => 'Choose a date within the next 30 days.',
            'documents.*.required' => 'Please upload all required documents.',
            'documents.*.mimes' => 'Please upload a PDF, JPG or PNG file.',
            'documents.*.max' => 'File is larger than 10 MB.',
            'consentShare.accepted' => 'Please accept both statements to continue.',
            'consentTerms.accepted' => 'Please accept both statements to continue.',
        ];
    }

    /** Insurer eligibility rules — the same checks as rules() in motor-quote.js. */
    public function after(): array
    {
        return [function (Validator $v) {
            $d = fn (string $k) => $this->dateInput($k);
            $today = CarbonImmutable::today();
            $fail = function (string $rule, ?string $message = null) {
                [$step, $code] = self::ELIGIBILITY[$rule];
                $this->eligibility[] = ['step' => $step, 'code' => $code, 'message' => $message ?? DinErrors::forCustomer($code)];
            };

            if ($dob = $d('dob')) {
                $age = (int) floor($dob->diffInYears($today));   // Carbon 3 returns a fraction
                if ($age < 25) {
                    $fail('age_min');
                }
                if ($age >= 65) {
                    $fail('age_max');
                }
            }
            if (($eid = $d('eidExpiry')) && $eid->lte($today)) {
                $fail('eid_expired');
            }
            if (($to = $d('licenseTo')) && $to->lte($today)) {
                $fail('licence_expired');
            }
            if (($from = $d('licenseFrom')) && $from->gt($today->subYear())) {
                $fail('licence_new');
            }
            // Older vehicles are not refused: they can still get Third Party Liability, just not comprehensive.
            if (is_numeric($this->input('year')) && $today->year - (int) $this->input('year') > self::MAX_AGE_COMPREHENSIVE
                && $this->input('coverType') === 'comprehensive') {
                $fail('old_vehicle_comprehensive', self::OLD_VEHICLE_MESSAGE);
            }
            if ($this->input('gccSpec') === 'no') {
                $fail('non_gcc');
            }
            if ($this->input('ev') === 'yes') {
                $fail('electric');
            }

            foreach ($this->eligibility as $e) {
                $v->errors()->add('eligibility', $e['message']);
            }
        }];
    }

    protected function failedValidation(Validator $validator): void
    {
        if (! $this->expectsJson()) {
            parent::failedValidation($validator);
        }

        $errors = $validator->errors()->toArray();
        unset($errors['eligibility']);
        $steps = [];
        foreach (array_keys($errors) as $field) {
            $steps[] = $this->stepOf($field);
        }
        foreach ($this->eligibility as $e) {
            $steps[] = $e['step'];
        }

        throw new HttpResponseException(response()->json([
            'message' => $this->eligibility ? 'This application can’t be completed online.' : 'Please check the highlighted fields.',
            'errors' => $errors,
            'eligibility' => $this->eligibility,
            'step' => $steps ? min($steps) : 1,
        ], 422));
    }

    public function stepOf(string $field): int
    {
        $root = explode('.', $field)[0];
        foreach (self::STEPS as $step => $fields) {
            if (in_array($root, $fields, true)) {
                return $step;
            }
        }

        return 1;
    }

    public function financed(): bool
    {
        return $this->input('financed') === 'yes';
    }

    public function usesSample(string $type): bool
    {
        return in_array($type, (array) $this->input('samples', []), true);
    }

    protected function dateInput(string $key): ?CarbonImmutable
    {
        try {
            $v = $this->input($key);

            return $v ? CarbonImmutable::parse($v)->startOfDay() : null;
        } catch (\Throwable) {
            return null;
        }
    }
}
