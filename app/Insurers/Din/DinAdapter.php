<?php

namespace App\Insurers\Din;

use App\Insurers\Contracts\InsurerAdapter;
use App\Insurers\Data\CheckoutResult;
use App\Insurers\Data\MotorQuoteInput;
use App\Insurers\Data\Offer;
use App\Insurers\Data\PolicyStatus;
use App\Insurers\Data\QuoteResult;
use App\Insurers\Support\InsurerHttp;
use Illuminate\Support\Facades\Cache;

/** Dubai Insurance — IIRIS Motor Policy API (JWT auth, X-REQUEST-ID per transaction). */
class DinAdapter implements InsurerAdapter
{
    protected InsurerHttp $http;

    public function __construct(protected array $config)
    {
        $this->http = new InsurerHttp('din', $config['base_url'], $config['timeout'] ?? 30);
    }

    public function code(): string
    {
        return 'din';
    }

    public function token(bool $fresh = false): ?string
    {
        $key = 'insurer:din:token';
        if ($fresh) {
            Cache::forget($key);
        }

        return Cache::remember($key, now()->addMinutes($this->config['token_ttl_minutes'] ?? 20), function () {
            [, $json] = $this->http->send('auth', 'POST', '/api/v1/User/Auth', [
                'userName' => $this->config['username'],
                'password' => $this->config['password'],
            ]);
            if (DinMapper::isOk($json) && is_string($json['data'] ?? null)) {
                return $json['data'];
            }
            throw new \RuntimeException('DIN auth failed: '.implode(' ', array_filter(DinMapper::error($json))));
        });
    }

    protected function call(string $op, string $method, string $path, array $body, string $requestId): array
    {
        $headers = fn () => ['Authorization' => 'Bearer '.$this->token(), 'X-REQUEST-ID' => $requestId];
        [$res, $json] = $this->http->send($op, $method, $path, $body, $headers(), $requestId);
        if ($res && in_array($res->status(), [401, 403], true)) {   // expired token -> retry once
            $this->token(true);
            [$res, $json] = $this->http->send($op, $method, $path, $body, $headers(), $requestId);
        }

        return $json;
    }

    public function ping(): array
    {
        try {
            $t = $this->token(true);

            return ['ok' => true, 'message' => 'Token generated ('.strlen($t).' chars)'];
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => $e->getMessage()];
        }
    }

    public function quote(MotorQuoteInput $input, string $requestId): QuoteResult
    {
        $offers = [];
        $tpl = [];
        $lastError = [null, null];
        foreach (DinMapper::repairVariants($input) as $i => $agency) {
            // DIN ties one quote to one X-REQUEST-ID, so each repair variant gets its own id.
            $rid = $i === 0 ? $requestId : $requestId.'-A';
            $json = $this->call('generateQuote', 'POST', '/api/v1/Insurance/GenerateQuote', DinMapper::quotePayload($input, $agency), $rid);
            if (DinMapper::isOk($json)) {
                // Keep every product DIN offers (it may return only Third Party for a vehicle even when
                // comprehensive was asked); the results page filters by cover type. Third Party does not
                // depend on the repair variant, so it is kept once.
                foreach (DinMapper::offers($json, $agency, $rid) as $offer) {
                    if ($offer->coverType === 'third_party') {
                        if (isset($tpl[$offer->planCode])) {
                            continue;
                        }
                        $tpl[$offer->planCode] = true;
                    }
                    $offers[] = $offer;
                }
            } else {
                $lastError = DinMapper::error($json);
            }
        }

        return $offers
            ? new QuoteResult('din', true, $offers, requestId: $requestId)
            : QuoteResult::fail('din', $lastError[0], DinErrors::forCustomer($lastError[0]) ?? $lastError[1], $requestId);
    }

    public function checkout(MotorQuoteInput $input, Offer $offer, array $optionalCovers, string $requestId): CheckoutResult
    {
        $rid = $offer->raw['request_id'] ?? $requestId;

        return DinMapper::checkout($this->call('chooseScheme', 'POST', '/api/v1/Insurance/ChooseScheme', DinMapper::chooseSchemePayload($offer, $optionalCovers), $rid));
    }

    public function policyStatus(string $requestId, array $context = []): PolicyStatus
    {
        return DinMapper::policy($this->call('getPaymentInfo', 'GET', '/api/v1/Insurance/GetPaymentInfo', [], $requestId));
    }
}
