<?php

namespace Tests\Feature;

use App\Insurers\InsurerManager;
use App\Models\ApiLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use Tests\Unit\DinMapperTest;

class DinAdapterTest extends TestCase
{
    use RefreshDatabase;

    public function test_quote_flow_with_faked_api_and_redacted_logs(): void
    {
        Http::fake([
            '*/api/v1/User/Auth' => Http::response(['statusId' => '8029', 'status' => 1, 'data' => str_repeat('t', 200)]),
            '*/api/v1/Insurance/GenerateQuote' => Http::response(['statusId' => '8006', 'status' => 1, 'data' => [
                ['prodCode' => '1001', 'prodName' => ['en' => '1001 - Comprehensive Gold'], 'sumInsured' => 50000, 'covers' => ['mandatory' => [['coverCode' => '10001', 'coverName' => ['en' => 'Own damage'], 'premium' => 1500]], 'optional' => []]],
            ]]),
        ]);

        $res = app(InsurerManager::class)->get('din')->quote(DinMapperTest::input(), 'rid-123');

        $this->assertTrue($res->ok);
        $this->assertCount(2, $res->offers);   // garage + agency variants
        Http::assertSent(fn ($r) => str_ends_with($r->url(), 'GenerateQuote') && $r->header('X-REQUEST-ID')[0] === 'rid-123');

        $log = ApiLog::where('operation', 'auth')->first();
        $this->assertSame('***', $log->request['body']['password']);
        $gen = ApiLog::where('operation', 'generateQuote')->first();
        $this->assertStringStartsWith('<', $gen->request['body']['documentLists'][0]['base64']);
        $this->assertSame('***', $gen->request['headers']['Authorization']);
    }

    public function test_customer_friendly_error(): void
    {
        Http::fake([
            '*/api/v1/User/Auth' => Http::response(['statusId' => '8029', 'status' => 1, 'data' => 'token']),
            '*/api/v1/Insurance/GenerateQuote' => Http::response(['statusId' => '8013', 'status' => 2, 'message' => ['en' => '8013:Civil id has been expired'], 'data' => []], 406),
        ]);

        $res = app(InsurerManager::class)->get('din')->quote(DinMapperTest::input(['repair' => 'garage']), 'rid-9');

        $this->assertFalse($res->ok);
        $this->assertSame('8013', $res->errorCode);
        $this->assertSame('Your Emirates ID has expired.', $res->errorMessage);
    }
}
