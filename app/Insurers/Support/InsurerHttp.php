<?php

namespace App\Insurers\Support;

use App\Models\ApiLog;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * HTTP wrapper used by every adapter: timeouts, JSON, and an api_logs row per call
 * with secrets and document content redacted.
 */
class InsurerHttp
{
    public function __construct(
        protected string $insurer,
        protected string $baseUrl,
        protected int $timeout = 30,
    ) {}

    public function request(): PendingRequest
    {
        return Http::baseUrl(rtrim($this->baseUrl, '/'))
            ->timeout($this->timeout)
            ->connectTimeout(10)
            ->acceptJson()
            ->asJson();
    }

    /**
     * @return array{0: ?Response, 1: array} response + decoded JSON ([] on failure)
     */
    public function send(string $operation, string $method, string $path, array $body = [], array $headers = [], ?string $requestId = null): array
    {
        $start = microtime(true);
        $response = null;
        $error = null;
        try {
            $req = $this->request()->withHeaders($headers);
            $response = strtoupper($method) === 'GET' ? $req->get($path, $body) : $req->send(strtoupper($method), $path, ['json' => $body]);
        } catch (ConnectionException $e) {
            $error = 'connection: '.$e->getMessage();
        } catch (\Throwable $e) {
            $error = class_basename($e).': '.$e->getMessage();
        }
        $json = $response ? ($response->json() ?? []) : [];
        if (! is_array($json)) {
            $json = ['_raw' => (string) $response?->body()];
        }

        try {
            ApiLog::create([
                'insurer' => $this->insurer,
                'operation' => $operation,
                'request_id' => $requestId,
                'method' => strtoupper($method),
                'url' => rtrim($this->baseUrl, '/').'/'.ltrim($path, '/'),
                'request' => Redactor::redact(['headers' => $headers, 'body' => $body]),
                'http_status' => $response?->status(),
                'insurer_status' => (string) ($json['statusId'] ?? $json['isSuccess'] ?? '') ?: null,
                'response' => Redactor::redact($json),
                'duration_ms' => (int) round((microtime(true) - $start) * 1000),
                'error' => $error ? mb_substr($error, 0, 250) : null,
            ]);
        } catch (\Throwable $e) {
            Log::warning('api_logs write failed', ['e' => $e->getMessage()]);
        }

        return [$response, $json];
    }
}
