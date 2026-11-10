<?php

namespace App\Insurers\Awnic;

use App\Insurers\Contracts\InsurerAdapter;
use App\Insurers\Data\CheckoutResult;
use App\Insurers\Data\MotorQuoteInput;
use App\Insurers\Data\Offer;
use App\Insurers\Data\PolicyStatus;
use App\Insurers\Data\QuoteResult;
use App\Insurers\Support\InsurerHttp;
use App\Models\InsurerCode;

/** Al Wathba — Insure API (Partner-Id + Api-Key headers, IP whitelisted). */
class AwnicAdapter implements InsurerAdapter
{
    protected InsurerHttp $http;

    public function __construct(protected array $config)
    {
        $this->http = new InsurerHttp('awnic', $config['base_url'], $config['timeout'] ?? 30);
    }

    public function code(): string
    {
        return 'awnic';
    }

    protected function headers(): array
    {
        return ['Partner-Id' => $this->config['partner_id'], 'Api-Key' => $this->config['api_key']];
    }

    public function post(string $op, string $path, array $body, ?string $requestId = null): array
    {
        return $this->http->send($op, 'POST', $path, $body, $this->headers(), $requestId)[1];
    }

    /** Translate a Delta/DIN code to AWNIC (insurer_codes rows with meta.din = code). */
    public function translator(): callable
    {
        return function (string $type, string $dinCode): ?string {
            return InsurerCode::where('insurer', 'awnic')->where('type', $type)
                ->where('meta->din', $dinCode)->value('code');
        };
    }

    public function ping(): array
    {
        $json = $this->post('masterList', '/motor/masterList', ['requestType' => 'ALL']);
        $ok = AwnicMapper::isOk($json);

        $error = implode(' ', array_filter(AwnicMapper::error($json))) ?: 'No response (is our IP whitelisted?)';

        return ['ok' => $ok, 'message' => $ok ? 'Master list received ('.count($json['masterList'] ?? []).' masters)' : $error];
    }

    /** Master data, e.g. masterData('NATIONALITY') or masterData('VEHICLE_MODEL_AD', ['MAKE_CODE' => 'BY200103']) */
    public function masterData(string $type, array $params = []): array
    {
        $body = ['masterType' => $type];
        if ($params) {
            $body['additionalParam'] = array_map(fn ($k, $v) => ['code' => $k, 'value' => $v], array_keys($params), $params);
        }

        return $this->post('masterData', '/motor/masterData', $body)['masterData'] ?? [];
    }

    public function vehicleDescriptions(array $codes): array
    {
        return $this->post('vehicleDescription', '/motor/motorADVechicleDescription', $codes)['vehicleDetails'] ?? [];
    }

    public function quote(MotorQuoteInput $input, string $requestId): QuoteResult
    {
        try {
            $payload = AwnicMapper::premiumPayload($input, $this->translator());
        } catch (MappingMissing $e) {
            return QuoteResult::fail('awnic', 'MAPPING', $e->getMessage(), $requestId);
        }
        $json = $this->post('calcPremium', '/motor/motorCalcPremiumAmwaj', $payload, $requestId);
        if (! AwnicMapper::isOk($json)) {
            [$c, $m] = AwnicMapper::error($json);

            return QuoteResult::fail('awnic', $c, $m, $requestId);
        }

        return new QuoteResult('awnic', true, AwnicMapper::offers($json, $payload['sumInsured'], $requestId, $input->coverType, $input->repair), requestId: $requestId);
    }

    public function checkout(MotorQuoteInput $input, Offer $offer, array $optionalCovers, string $requestId): CheckoutResult
    {
        $cfg = $this->config + [
            'success_url' => config('insurers.payment.success_url'),
            'fail_url' => config('insurers.payment.fail_url'),
        ];
        try {
            $payload = AwnicMapper::initializePayload($input, $offer, $optionalCovers, $requestId, $cfg, $this->translator());
        } catch (MappingMissing $e) {
            return new CheckoutResult(false, errorCode: 'MAPPING', errorMessage: $e->getMessage());
        }

        return AwnicMapper::checkout($this->post('initializePolicy', '/motor/initializePolicyGeneration', $payload, $requestId));
    }

    /** context: ['awnic_quote_no' => ..., 'premium' => ...] — called after the customer returns from payment. */
    public function policyStatus(string $requestId, array $context = []): PolicyStatus
    {
        return AwnicMapper::policy($this->post('finalisePolicy', '/motor/finalisePolicyGeneration', [
            'quoteNo' => $requestId,
            'awnicQuoteNo' => $context['awnic_quote_no'] ?? '',
            'premium' => (string) ($context['premium'] ?? ''),
        ], $requestId));
    }

    public function downloadDocument(string $docId): array
    {
        return $this->post('downloadDocument', '/motor/motorDownloadDocument', ['docId' => $docId]);
    }

    public function uploadDocument(string $policyNo, string $category, string $name, string $base64): array
    {
        return $this->post('uploadDocument', '/motor/motorUploadDocument', ['docCategory' => $category, 'docContent' => $base64, 'docName' => $name, 'policyNo' => $policyNo]);
    }
}
