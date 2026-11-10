<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Website lead forms: the five product pages ("Request a quote"), the contact page and Claims & Support. */
class LeadRequest extends FormRequest
{
    public const SOURCES = ['product', 'contact', 'claims'];

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['mobile' => preg_replace('/\D/', '', (string) $this->input('mobile'))]);
    }

    public function rules(): array
    {
        $claims = $this->input('source') === 'claims';

        return [
            'source' => ['required', Rule::in(self::SOURCES)],
            'name' => ['required', 'string', 'max:120'],
            // UAE mobile entered after the +971 prefix: 5XXXXXXXX (a leading 0 or 971 is tolerated).
            'mobile' => ['required', 'regex:/^(971|0)?5\d{8}$/'],
            'email' => ['required', 'email:rfc', 'max:150'],
            'product' => ['required', Rule::in([...array_keys(config('site.services')), 'general', 'claims'])],
            'message' => [Rule::requiredIf(in_array($this->input('source'), ['contact', 'claims'], true)), 'nullable', 'string', 'max:2000'],
            'contactPref' => ['nullable', Rule::in(config('site.contact_prefs'))],
            'emirate' => ['nullable', Rule::in(config('site.emirates'))],
            // Claims & Support only
            'requestType' => [Rule::requiredIf($claims), 'nullable', Rule::in(array_keys(config('site.claims.request_types')))],
            'policyNo' => ['nullable', 'string', 'max:40'],
            'insurer' => ['nullable', Rule::in([...array_keys(config('insurers.providers')), 'other'])],
            'consent' => ['accepted'],
            'company_website' => ['nullable', 'string'],   // honeypot: see isSpam()
        ];
    }

    public function messages(): array
    {
        return [
            'mobile.regex' => 'Please enter a UAE mobile number.',
            'consent.accepted' => 'Please agree to be contacted about this request.',
            'message.required' => 'Please write your message.',
            'requestType.required' => 'Please tell us what you need help with.',
        ];
    }

    public function isSpam(): bool
    {
        return filled($this->input('company_website'));
    }
}
