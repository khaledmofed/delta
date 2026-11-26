<?php

namespace Tests\Feature;

use App\Insurers\Data\PolicyStatus;
use App\Insurers\InsurerManager;
use App\Models\ApiLog;
use App\Models\InsurerCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use Tests\Unit\DinMapperTest;
use Tests\Unit\QicMapperTest;

/** QIC adapter against a faked API (test credentials and data only). */
class QicAdapterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['insurers.providers.qic' => array_merge(config('insurers.providers.qic'), [
            'enabled' => true, 'base_url' => 'https://qic.test/api-gateway/qicservices-uae',
            'username' => 'test-user', 'password' => 'test-pass', 'company' => '002', 'pay_user_id' => 'delta.test',
        ])]);
        $this->app->forgetInstance(InsurerManager::class);
        foreach ([['nationality', '092', '102'], ['emirate', '002', '02']] as [$type, $code, $din]) {
            InsurerCode::create(['insurer' => 'qic', 'type' => $type, 'code' => $code, 'label' => $type, 'meta' => ['din' => $din]]);
        }
    }

    private function qic()
    {
        return app(InsurerManager::class)->get('qic');
    }

    public function test_quote_sends_basic_auth_and_company_and_logs_without_secrets(): void
    {
        Http::fake(['qic.test/*/motor/tariff*' => Http::response(QicMapperTest::tariffResponse())]);

        $res = $this->qic()->quote(DinMapperTest::input(['extra' => QicMapperTest::vehicle()]), 'rid-q1');

        $this->assertTrue($res->ok);
        $this->assertCount(2, $res->offers);                            // comprehensive asked, any repair: agency + non-agency
        $this->assertSame('Q-TEST-0001', $res->context['qic_quote_no']);
        Http::assertSent(fn (Request $r) => $r->url() === 'https://qic.test/api-gateway/qicservices-uae/motor/tariff?company=002'
            && $r->header('Authorization')[0] === 'Basic '.base64_encode('test-user:test-pass')
            && $r->header('company')[0] === '002'
            && $r['nationality'] === '092' && $r['regnLocation'] === '002');

        $log = ApiLog::where('insurer', 'qic')->where('operation', 'tariff')->first();
        $this->assertSame('***', $log->request['headers']['Authorization']);
        $this->assertSame('rid-q1', $log->request_id);
    }

    public function test_quote_error_and_missing_mapping(): void
    {
        Http::fake(['qic.test/*' => Http::response(['respCode' => '4002', 'errMessage' => 'Invalid model code'])]);
        $res = $this->qic()->quote(DinMapperTest::input(['extra' => QicMapperTest::vehicle()]), 'rid-q2');
        $this->assertFalse($res->ok);
        $this->assertSame(['4002', 'Invalid model code'], [$res->errorCode, $res->errorMessage]);

        $res = $this->qic()->quote(DinMapperTest::input(), 'rid-q3');      // no QIC vehicle codes: nothing sent
        $this->assertSame('MAPPING', $res->errorCode);
        Http::assertSentCount(1);
    }

    public function test_checkout_gets_final_premium_then_payment_link(): void
    {
        Http::fake([
            'qic.test/*/motor/netPremium*' => Http::response(['quoteNo' => 'Q-TEST-0001', 'netPremium' => 2257.5, 'taxAmount' => 107.5]),
            'qic.test/*/sendPayLink*' => Http::response(['respCode' => '2000', 'payLink' => 'https://pay.example/qic/1']),
        ]);
        $in = DinMapperTest::input(['extra' => QicMapperTest::vehicle()]);
        $offer = \App\Insurers\Qic\QicMapper::offers(QicMapperTest::tariffResponse(), 85000.0, 'rid-c1')[0];

        $res = $this->qic()->checkout($in, $offer, ['C10'], 'rid-c1');

        $this->assertTrue($res->ok);
        $this->assertSame('https://pay.example/qic/1', $res->paymentUrl);
        $this->assertSame('Q-TEST-0001', $res->insurerQuoteNo);
        $this->assertSame(2257.5, $res->total);
        Http::assertSent(fn (Request $r) => str_contains($r->url(), 'motor/netPremium?company=002')
            && $r['schemes'][0] === ['schemeCode' => '0297', 'productCode' => '0110', 'optionalCovers' => [['coverCode' => 'C10']]]);
        Http::assertSent(fn (Request $r) => str_contains($r->url(), 'sendPayLink?company=002')
            && $r['quoteNo'] === 'Q-TEST-0001' && $r['userId'] === 'delta.test' && $r['userType'] === 'A' && $r['emailId'] === $in->email);
    }

    public function test_checkout_stops_when_net_premium_fails(): void
    {
        Http::fake(['qic.test/*/motor/netPremium*' => Http::response(['respCode' => '4010', 'errMessage' => 'Quotation expired'])]);
        $offer = \App\Insurers\Qic\QicMapper::offers(QicMapperTest::tariffResponse(), 85000.0, 'rid-c2')[0];

        $res = $this->qic()->checkout(DinMapperTest::input(['extra' => QicMapperTest::vehicle()]), $offer, [], 'rid-c2');

        $this->assertFalse($res->ok);
        $this->assertSame('Quotation expired', $res->errorMessage);
        Http::assertNotSent(fn (Request $r) => str_contains($r->url(), 'sendPayLink'));
    }

    public function test_policy_status_finds_the_policy_and_downloads_the_schedule(): void
    {
        $pdf = '%PDF-1.4 test schedule '.str_repeat('x', 300);
        Http::fake([
            'qic.test/*/getLeadPolList*' => Http::response(['policyList' => [['quoteNo' => 'Q-TEST-0001', 'policyNo' => 'P-TEST-0009', 'activeYn' => 'Y']]]),
            'qic.test/*/getPolicyReport*' => Http::response($pdf, 200, ['Content-Type' => 'application/pdf']),
        ]);

        $st = $this->qic()->policyStatus('rid-p1', ['insurer_quote_no' => 'Q-TEST-0001', 'chassis' => 'TESTCHASSIS000001']);

        $this->assertSame(PolicyStatus::ISSUED, $st->status);
        $this->assertSame('P-TEST-0009', $st->policyNo);
        $this->assertSame($pdf, base64_decode($st->documents['policy_schedule.pdf']));
        Http::assertSent(fn (Request $r) => str_contains($r->url(), 'getLeadPolList?company=002') && $r['chassisNo'] === 'TESTCHASSIS000001');
        Http::assertSent(fn (Request $r) => $r->method() === 'GET' && str_contains($r->url(), 'getPolicyReport')
            && str_contains($r->url(), 'company=002') && str_contains($r->url(), 'docType=Schedule') && str_contains($r->url(), 'policyNo=P-TEST-0009'));
        $this->assertStringNotContainsString('%PDF', json_encode(ApiLog::where('operation', 'getPolicyReport')->first()->response));
    }

    public function test_policy_status_stays_pending_until_issued(): void
    {
        Http::fake(['qic.test/*/getLeadPolList*' => Http::response(['policyList' => []])]);
        $st = $this->qic()->policyStatus('rid-p2', ['insurer_quote_no' => 'Q-TEST-0001', 'chassis' => 'TESTCHASSIS000001']);
        $this->assertSame(PolicyStatus::PENDING, $st->status);
        $this->assertNull($st->paymentStatus);

        $this->assertSame('CONTEXT', $this->qic()->policyStatus('rid-p3', [])->errorCode);   // nothing to look up with
        Http::assertSentCount(1);
    }

    public function test_ping_reports_whitelisting_credentials_and_success(): void
    {
        Http::fake(['qic.test/*' => Http::sequence()
            ->push(['respCode' => '4000', 'errMessage' => 'makeCode is required'], 400)
            ->push(['message' => 'Unauthorized'], 401)
            ->pushFailedConnection()]);

        $this->assertSame(['ok' => true, 'message' => 'Authenticated (HTTP 400, QIC: makeCode is required)'], $this->qic()->ping());
        $this->assertSame(['ok' => false, 'message' => 'Credentials rejected (HTTP 401)'], $this->qic()->ping());
        $this->assertSame(['ok' => false, 'message' => 'No response (is our IP whitelisted?)'], $this->qic()->ping());
    }

    public function test_qic_is_off_until_enabled_in_env(): void
    {
        config(['insurers.providers.qic.enabled' => false]);
        $this->app->forgetInstance(InsurerManager::class);
        $this->assertArrayNotHasKey('qic', app(InsurerManager::class)->forProduct('motor'));

        config(['insurers.providers.qic.enabled' => true]);
        $this->app->forgetInstance(InsurerManager::class);
        $this->assertArrayHasKey('qic', app(InsurerManager::class)->forProduct('motor'));   // admin switch follows .env
    }
}
