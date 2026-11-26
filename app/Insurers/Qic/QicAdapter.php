<?php

namespace App\Insurers\Qic;

use App\Insurers\Contracts\InsurerAdapter;
use App\Insurers\Data\CheckoutResult;
use App\Insurers\Data\MotorQuoteInput;
use App\Insurers\Data\Offer;
use App\Insurers\Data\PolicyStatus;
use App\Insurers\Data\QuoteResult;
use App\Insurers\Support\InsurerHttp;
use App\Models\InsurerCode;

/**
 * QIC (Qatar Insurance – UAE), Motor API by Anoud Technologies.
 * HTTP Basic auth + company code (002 = Dubai) as a header and ?company= on every call; IP whitelisted.
 * Bayanaty (vehicle data by chassis / make-model-trim) lives on the same gateway.
 */
class QicAdapter implements InsurerAdapter
{
    protected InsurerHttp $http;

    public function __construct(protected array $config)
    {
        $this->http = new InsurerHttp('qic', $config['base_url'], $config['timeout'] ?? 30);
    }

    public function code(): string
    {
        return 'qic';
    }

    protected function company(): string
    {
        return (string) ($this->config['company'] ?? '002');
    }

    protected function headers(): array
    {
        return [
            'Authorization' => 'Basic '.base64_encode(($this->config['username'] ?? '').':'.($this->config['password'] ?? '')),
            'company' => $this->company(),
        ];
    }

    /** POST with ?company= in the path. Returns [response, json]. */
    public function post(string $op, string $path, array $body, ?string $requestId = null): array
    {
        return $this->http->send($op, 'POST', ltrim($path, '/').'?company='.$this->company(), $body, $this->headers(), $requestId);
    }

    /** GET with company as a query parameter (Guzzle would drop a query string written in the path). */
    public function get(string $op, string $path, array $query, ?string $requestId = null): array
    {
        return $this->http->send($op, 'GET', ltrim($path, '/'), ['company' => $this->company()] + $query, $this->headers(), $requestId);
    }

    /** Translate a Delta/DIN code to QIC (insurer_codes rows with meta.din = code). */
    public function translator(): callable
    {
        return function (string $type, string $dinCode): ?string {
            return InsurerCode::where('insurer', 'qic')->where('type', $type)
                ->where('meta->din', $dinCode)->value('code');
        };
    }

    /**
     * The spec has no ping/master-list call: send an empty tariff request. A validation answer from QIC means we
     * reached the API and the credentials were accepted; 401/403 means they were not; no answer = IP not whitelisted.
     */
    public function ping(): array
    {
        [$res, $json] = $this->post('ping', 'motor/tariff', []);
        if (! $res) {
            return ['ok' => false, 'message' => 'No response (is our IP whitelisted?)'];
        }
        if (in_array($res->status(), [401, 403], true)) {
            return ['ok' => false, 'message' => 'Credentials rejected (HTTP '.$res->status().')'];
        }
        if ($res->status() >= 500 && ! $json) {
            return ['ok' => false, 'message' => 'QIC server error (HTTP '.$res->status().')'];
        }
        [, $msg] = QicMapper::error($json);

        return ['ok' => true, 'message' => 'Authenticated (HTTP '.$res->status().($json ? ', QIC: '.mb_substr($msg, 0, 80) : '').')'];
    }

    public function quote(MotorQuoteInput $input, string $requestId): QuoteResult
    {
        try {
            $payload = QicMapper::tariffPayload($input, $this->translator());
        } catch (MappingMissing $e) {
            return QuoteResult::fail('qic', 'MAPPING', $e->getMessage(), $requestId);
        }
        [, $json] = $this->post('tariff', 'motor/tariff', $payload, $requestId);
        if (! QicMapper::isOk($json, 'quoteNo')) {
            [$c, $m] = QicMapper::error($json);

            return QuoteResult::fail('qic', $c, $m, $requestId);
        }
        $offers = QicMapper::offers($json, $payload['sumInsured'] ?: null, $requestId, $input->coverType, $input->repair);

        return new QuoteResult('qic', true, $offers, requestId: $requestId, context: ['qic_quote_no' => (string) $json['quoteNo']]);
    }

    /** Final premium for the chosen scheme, then QIC's payment link. */
    public function checkout(MotorQuoteInput $input, Offer $offer, array $optionalCovers, string $requestId): CheckoutResult
    {
        if (! $offer->insurerRef) {
            return new CheckoutResult(false, errorCode: 'NO_QUOTE', errorMessage: 'QIC quotation number missing on the offer.');
        }
        [, $premium] = $this->post('netPremium', 'motor/netPremium', QicMapper::netPremiumPayload($offer, $optionalCovers), $requestId);
        if (! QicMapper::isOk($premium, 'netPremium')) {
            return QicMapper::checkout($offer->insurerRef, $premium, []);
        }
        [, $link] = $this->post('sendPayLink', 'sendPayLink', QicMapper::payLinkPayload($offer->insurerRef, $input, $this->config), $requestId);

        return QicMapper::checkout($offer->insurerRef, $premium, $link);
    }

    /**
     * context: ['insurer_quote_no' => QIC quote no., 'chassis' => ..., 'mobile' => ...].
     * Finds the issued policy by chassis and downloads the schedule (PDF) once it exists.
     */
    public function policyStatus(string $requestId, array $context = []): PolicyStatus
    {
        $quoteNo = (string) ($context['insurer_quote_no'] ?? $context['awnic_quote_no'] ?? '');
        $lookup = QicMapper::policyLookupPayload($context);
        if ($quoteNo === '' || ! $lookup) {
            return new PolicyStatus(PolicyStatus::PENDING, errorCode: 'CONTEXT', errorMessage: 'QIC quote number and chassis/mobile are needed to find the policy.');
        }
        [, $json] = $this->post('getLeadPolList', 'getLeadPolList', $lookup, $requestId);
        [$status] = QicMapper::policy($json, $quoteNo);
        if ($status->status !== PolicyStatus::ISSUED) {
            return $status;
        }
        $pdf = $this->policyDocument($status->policyNo, 'Schedule', $requestId);

        return new PolicyStatus(PolicyStatus::ISSUED, policyNo: $status->policyNo, paymentStatus: 'S', total: $status->total,
            documents: $pdf ? ['policy_schedule.pdf' => base64_encode($pdf)] : [], raw: $status->raw);
    }

    /** getPolicyReport: docType Schedule | Receipt | Wordings. Returns the PDF bytes or null. */
    public function policyDocument(string $policyNo, string $docType = 'Schedule', ?string $requestId = null): ?string
    {
        return $this->document('getPolicyReport', ['docType' => $docType, 'policyNo' => $policyNo], $requestId);
    }

    /** getQuoteSchedule: the quotation PDF. */
    public function quoteDocument(string $quoteNo, ?string $requestId = null): ?string
    {
        return $this->document('getQuoteSchedule', ['quoteNo' => $quoteNo, 'docType' => 'Quotation'], $requestId);
    }

    /** The spec says a PDF is "downloaded"; accept raw PDF bytes or a JSON wrapper with base64 content. */
    protected function document(string $op, array $query, ?string $requestId): ?string
    {
        [$res, $json] = $this->get($op, $op, $query, $requestId);
        if (! $res || ! $res->successful()) {
            return null;
        }
        $body = $res->body();
        if (str_starts_with($body, '%PDF')) {
            return $body;
        }
        foreach (['document', 'fileContent', 'base64', 'data', 'content'] as $k) {
            if (is_string($json[$k] ?? null) && ($bin = base64_decode($json[$k], true)) !== false && str_starts_with($bin, '%PDF')) {
                return $bin;
            }
        }

        return null;
    }

    // ---- Bayanaty (vehicle data) — used to fill the QIC vehicle codes in MotorQuoteInput::$extra['qic'] ----

    /** Vehicle details by chassis (make/model/trim ids, imported/GCC, mileage, values). */
    public function bayanatyVehicle(string $vin): array
    {
        return $this->post('bayanatyVehicleDetails', 'bayanaty/vehicleDetails', ['Vin' => $vin])[1];
    }

    /** Bayanaty ids -> QIC makeCode / modelCode / admeId. */
    public function qicCodes(int $makeId, int $modelId, int $trimId, ?int $bodyTypeId = null): array
    {
        return $this->post('bayanatyQicCodes', 'bayanaty/getQicCodes', array_filter(['MakeId' => $makeId, 'ModelId' => $modelId, 'TrimId' => $trimId, 'BodyTypeId' => $bodyTypeId]))[1];
    }

    /** Market value range for a make/model/trim/year (sum insured). */
    public function valuation(int $year, int $makeId, int $modelId, int $trimId): array
    {
        return $this->post('bayanatyValuation', 'bayanaty/vehicleValuationV2', ['ModelYear' => $year, 'MakeId' => $makeId, 'ModelId' => $modelId, 'TrimId' => $trimId])[1];
    }
}
