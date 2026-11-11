<?php

namespace Tests\Feature;

use App\Jobs\RunQuoteEngine;
use App\Models\Document;
use App\Models\Order;
use App\Models\QuoteRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\Feature\Concerns\BuildsMotorJourney;
use Tests\TestCase;

/**
 * Whole motor journey against faked insurer APIs:
 * wizard -> MotorQuoteRequest -> QuoteRequest + private documents -> RunQuoteEngine job -> results (polling)
 * -> select offer -> Order -> checkout (DIN ChooseScheme) -> insurer payment page -> return -> policyStatus.
 */
class MotorJourneyTest extends TestCase
{
    use BuildsMotorJourney, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpMotorJourney();
    }

    public function test_wizard_page_renders_with_enabled_insurers(): void
    {
        $this->get('/motor-quote')
            ->assertOk()
            ->assertSee('Get your motor insurance quote')
            ->assertSee('id="quoteForm"', false)
            ->assertSee('Dubai Insurance')
            ->assertSee('Al Wathba Insurance')
            ->assertDontSee('id="allSamples"', false);   // demo mode off
    }

    public function test_full_journey_from_quote_to_issued_policy(): void
    {
        $this->fakeDin($this->dinOffers(), [
            '*/api/v1/Insurance/ChooseScheme' => Http::response(['statusId' => '8007', 'status' => 1, 'data' => [
                'paymentUrl' => 'https://uatbrokerportal.dubins.ae/pay/abc', 'quotationNo' => 'Q-777',
                'netPremium' => 1600, 'vat' => 80, 'netToCustomer' => 1680, 'excess' => 500,
            ]]),
            '*/api/v1/Insurance/GetPaymentInfo' => Http::response(['statusId' => '8200', 'status' => 1, 'data' => [
                'polNo' => 'P/01/2026/000123', 'polStatus' => 'Approved', 'paymentStatus' => 'S', 'payable' => 1680,
            ]]),
        ]);

        // 1) Wizard submit: validated, stored, quote engine job runs (sync queue in tests).
        $res = $this->submit($this->form())->assertOk()->assertJsonStructure(['reference', 'redirect']);
        $qr = QuoteRequest::where('reference', $res->json('reference'))->firstOrFail();
        $this->assertSame('QUOTES_RECEIVED', $qr->status);
        $this->assertSame('784198612345671', $qr->input['emirates_id']);
        $this->assertSame(['roadside'], $qr->input['addons']);

        // Documents: on the private disk, recorded with the DIN document code, sent as base64 to DIN.
        $this->assertSame(6, $qr->documents()->count());
        $doc = $qr->documents()->where('type', 'eid_front')->first();
        $this->assertSame('103', $doc->insurer_code);
        Storage::disk('local')->assertExists($doc->path);
        $this->assertStringStartsWith("quote-requests/{$qr->reference}/", $doc->path);
        Http::assertSent(fn ($r) => str_ends_with($r->url(), 'GenerateQuote') && count($r['documentLists']) === 6 && $r['documentLists'][0]['code'] === '103' && $r['nationalId'] === '784198612345671');

        // 2) Results: DIN offers (garage + agency) and AWNIC's failure side by side.
        $this->get($res->json('redirect'))->assertOk()->assertSee('Your motor insurance offers')->assertSee($qr->reference);
        $status = $this->getJson(route('motor.status', $qr))->assertOk();
        $this->assertTrue($status->json('done'));
        $this->assertCount(2, $status->json('quotes'));
        $insurers = collect($status->json('insurers'))->keyBy('code');
        $this->assertSame('success', $insurers['din']['status']);
        $this->assertSame('error', $insurers['awnic']['status']);
        $this->assertStringNotContainsString('mapped', $insurers['awnic']['message']);   // no technical text for customers

        // 3) Compare two offers.
        $ids = collect($status->json('quotes'))->pluck('id');
        $this->get(route('motor.compare', $qr).'?ids='.$ids->join(','))->assertOk()->assertSee('Own damage')->assertSee('Roadside Assistance');

        // 4) Select an offer -> Order with the matching optional cover (roadside add-on).
        $quoteId = $ids->first();
        $this->post(route('motor.select', $quoteId))->assertRedirect();
        $order = Order::firstOrFail();
        $this->assertSame('QUOTE_SELECTED', $order->state);
        $this->assertSame(['20001'], $order->selected_covers);
        $this->assertEquals(1680.0, (float) $order->total);   // (1500 + 100) * 1.05
        $this->get(route('motor.checkout', $order))->assertOk()->assertSee('Complete your application')->assertSee('Roadside Assistance')->assertSee('784-1986-•••••••-1');

        // 5) Checkout -> adapter->checkout() (ChooseScheme) -> redirect to the insurer payment page.
        $this->post(route('motor.checkout.submit', $order), ['name' => 'Omar Al Mansoori', 'email' => 'omar@example.com', 'mobile' => '501004000', 'policyStart' => now()->addDays(2)->toDateString(), 'd1' => 'on', 'd2' => 'on', 'd3' => 'on'])
            ->assertRedirect('https://uatbrokerportal.dubins.ae/pay/abc');
        Http::assertSent(fn ($r) => str_ends_with($r->url(), 'ChooseScheme') && $r['prodCode'] === '1001' && $r['covers']['optional'] === '20001');
        $order->refresh();
        $this->assertSame('PAYMENT_PENDING', $order->state);
        $this->assertSame('Q-777', $order->insurer_quote_no);
        $this->assertSame('pending', $order->payments()->first()->status);

        // 6) Return from payment: the URL says "fail" but the insurer says paid -> the insurer wins.
        $this->get('/motor/payment/return/fail')->assertRedirect(route('motor.confirmation', $order));
        Http::assertSent(fn ($r) => str_contains($r->url(), 'GetPaymentInfo') && $r->header('X-REQUEST-ID')[0] === $order->request_id);
        $order->refresh();
        $this->assertSame('POLICY_ISSUED', $order->state);
        $this->assertSame('success', $order->payments()->first()->status);
        $this->assertSame('P/01/2026/000123', $order->policy->policy_no);
        $this->assertSame(now()->addDays(2)->toDateString(), $order->policy->starts_on->toDateString());

        $this->get(route('motor.confirmation', $order))->assertOk()->assertSee('You’re insured!')->assertSee('P/01/2026/000123');
    }

    public function test_din_8013_is_shown_politely_and_other_insurers_stay_visible(): void
    {
        $this->fakeDin([['statusId' => '8013', 'status' => 2, 'message' => ['en' => '8013:Civil id has been expired'], 'data' => []], 406]);

        $ref = $this->submit($this->form())->assertOk()->json('reference');
        $qr = QuoteRequest::where('reference', $ref)->firstOrFail();
        $this->assertSame('NO_QUOTES', $qr->status);

        $status = $this->getJson(route('motor.status', $qr))->assertOk();
        $this->assertTrue($status->json('done'));
        $din = collect($status->json('insurers'))->firstWhere('code', 'din');
        $this->assertSame('error', $din['status']);
        $this->assertSame('8013', $din['errorCode']);
        $this->assertSame('Your Emirates ID has expired.', $din['message']);
        $this->assertCount(2, $status->json('insurers'));   // AWNIC still listed with its own status

        // The raw insurer text never reaches the page.
        $this->get(route('motor.results', $qr))->assertOk()->assertSee('Your Emirates ID has expired.')->assertDontSee('Civil id has been expired');
    }

    public function test_offers_from_one_insurer_survive_another_insurers_exception(): void
    {
        $this->fakeDin($this->dinOffers());
        config(['insurers.providers.awnic.adapter' => ExplodingAdapter::class]);

        $ref = $this->submit($this->form(['repair' => 'garage']))->assertOk()->json('reference');
        $status = $this->getJson(route('motor.status', QuoteRequest::where('reference', $ref)->first()))->assertOk();

        $this->assertCount(1, $status->json('quotes'));
        $awnic = collect($status->json('insurers'))->firstWhere('code', 'awnic');
        $this->assertSame('error', $awnic['status']);
        $this->assertStringNotContainsString('boom', $awnic['message']);
    }

    public function test_quote_engine_runs_as_a_queued_job_and_results_poll_while_pending(): void
    {
        Queue::fake();
        $ref = $this->submit($this->form())->assertOk()->json('reference');
        $qr = QuoteRequest::where('reference', $ref)->firstOrFail();

        Queue::assertPushed(RunQuoteEngine::class, fn ($job) => $job->quoteRequestId === $qr->id);
        $status = $this->getJson(route('motor.status', $qr))->assertOk();
        $this->assertFalse($status->json('done'));
        $this->assertSame(['pending', 'pending'], collect($status->json('insurers'))->pluck('status')->all());

        // An insurer that never answers stops the polling after the wait limit.
        $this->travel(4)->minutes();
        $this->assertTrue($this->getJson(route('motor.status', $qr))->json('done'));
    }

    public function test_eligibility_rules_match_the_wizard(): void
    {
        Queue::fake();
        $res = $this->submit($this->form([
            'dob' => now()->subYears(22)->toDateString(),
            'licenseFrom' => now()->subMonths(6)->toDateString(),
            'year' => (string) now()->subYears(12)->year,
            'gccSpec' => 'no',
            'ev' => 'yes',
        ]))->assertStatus(422);

        $this->assertEqualsCanonicalizing(['8014', '8026', '8017', '8024', '8023'], collect($res->json('eligibility'))->pluck('code')->all());
        $this->assertSame(1, $res->json('step'));
        $this->assertContains('Online cover is available for drivers aged 25 or over.', collect($res->json('eligibility'))->pluck('message')->all());
        Queue::assertNothingPushed();
        $this->assertSame(0, QuoteRequest::count());

        $this->submit($this->form(['dob' => now()->subYears(65)->toDateString(), 'eidExpiry' => now()->toDateString(), 'licenseTo' => now()->subDay()->toDateString()]))
            ->assertStatus(422)
            ->assertJsonPath('eligibility.0.code', '8015')
            ->assertJsonFragment(['code' => '8013'])
            ->assertJsonFragment(['code' => '8016']);
    }

    /** Vehicles older than 10 years: comprehensive is refused with an explanation, third party goes through. */
    public function test_old_vehicles_can_only_get_third_party_online(): void
    {
        Queue::fake();
        $old = (string) now()->subYears(12)->year;

        $res = $this->submit($this->form(['year' => $old, 'coverType' => 'comprehensive']))->assertStatus(422);
        $this->assertSame([['step' => 4, 'code' => '8017', 'message' => 'Comprehensive cover is not available online for vehicles older than 10 years. Please choose Third Party Liability.']], $res->json('eligibility'));
        $this->assertSame(4, $res->json('step'));
        $this->assertSame(0, QuoteRequest::count());

        $ref = $this->submit($this->form(['year' => $old, 'coverType' => 'third_party', 'repair' => null]))->assertOk()->json('reference');
        $qr = QuoteRequest::where('reference', $ref)->first();
        $this->assertSame('third_party', $qr->input['cover_type']);
        $this->assertSame((int) $old, $qr->input['year']);
        Queue::assertPushed(RunQuoteEngine::class);

        // Exactly 10 years old is still fine for comprehensive.
        $this->submit($this->form(['year' => (string) now()->subYears(10)->year, 'coverType' => 'comprehensive']))->assertOk();
    }

    public function test_wizard_explains_the_old_vehicle_rule(): void
    {
        $this->get('/motor-quote')->assertOk()
            ->assertSee('data-old-car-note', false)
            ->assertSee('Comprehensive isn’t available online for this vehicle.')
            ->assertSee('data-elig="4"', false);
    }

    public function test_field_validation_and_documents(): void
    {
        Queue::fake();
        $form = $this->form(['mobile' => '12345', 'emiratesId' => '123', 'chassis' => 'SHORT', 'nationality' => '999', 'policyStart' => now()->addDays(45)->toDateString(), 'consentTerms' => null, 'financed' => 'yes']);
        unset($form['documents']['mulkiya_back']);

        $res = $this->submit($form)->assertStatus(422);
        foreach (['mobile', 'emiratesId', 'chassis', 'nationality', 'policyStart', 'consentTerms', 'bank', 'documents.mulkiya_back', 'documents.bank_lpo'] as $field) {
            $res->assertJsonValidationErrors($field);
        }
        $this->assertSame([], $res->json('eligibility'));

        // Wrong file type; demo samples are refused outside demo mode.
        $form = $this->form(['samples' => ['eid_back']]);
        $form['documents']['eid_front'] = UploadedFile::fake()->create('virus.exe', 10, 'application/x-msdownload');
        unset($form['documents']['eid_back']);
        $this->submit($form)->assertStatus(422)->assertJsonValidationErrors(['documents.eid_front', 'samples.0']);
    }

    public function test_demo_mode_sample_documents_are_stored_privately(): void
    {
        Queue::fake();
        config(['site.demo' => true]);
        $form = $this->form(['samples' => ['eid_front', 'eid_back']]);
        unset($form['documents']['eid_front'], $form['documents']['eid_back']);

        $ref = $this->submit($form)->assertOk()->json('reference');
        $docs = QuoteRequest::where('reference', $ref)->first()->documents;
        $this->assertCount(6, $docs);
        $this->assertSame('sample', $docs->firstWhere('type', 'eid_front')->status);
        Storage::disk('local')->assertExists($docs->firstWhere('type', 'eid_front')->path);
    }

    public function test_quotes_and_orders_belong_to_the_session_that_created_them(): void
    {
        Queue::fake();
        $ref = $this->submit($this->form())->assertOk()->json('reference');
        $qr = QuoteRequest::where('reference', $ref)->first();

        $this->flushSession();
        $this->get(route('motor.results', $qr))->assertNotFound();
        $this->getJson(route('motor.status', $qr))->assertNotFound();
    }

    public function test_payment_states_follow_the_insurer_not_the_return_url(): void
    {
        [$order] = $this->orderAwaitingPayment([
            '*/api/v1/Insurance/GetPaymentInfo' => Http::sequence()
                ->push(['statusId' => '8203', 'status' => 2, 'message' => ['en' => 'Payment not completed'], 'data' => []], 406)
                ->push(['statusId' => '8200', 'status' => 1, 'data' => ['polStatus' => 'Pending', 'paymentStatus' => 'F']]),
        ]);

        // "success" in the URL, but the insurer has no payment yet -> still waiting.
        $this->get('/motor/payment/return/success')->assertRedirect(route('motor.confirmation', $order));
        $this->assertSame('PAYMENT_PENDING', $order->fresh()->state);

        // Next check: the insurer reports a declined payment -> retry is offered.
        $this->artisan('orders:poll-policies')->assertSuccessful();
        $this->assertSame('PAYMENT_FAILED', $order->fresh()->state);
        $this->assertSame('failed', $order->payments()->first()->status);
        $this->get(route('motor.confirmation', $order))->assertOk()->assertSee('Payment not completed');
        $this->get(route('motor.payment', $order))->assertOk()->assertSee('Your last payment was not completed.');
    }

    public function test_scheduled_poll_issues_the_policy_when_the_customer_never_returns(): void
    {
        [$order] = $this->orderAwaitingPayment([
            '*/api/v1/Insurance/GetPaymentInfo' => Http::response(['statusId' => '8200', 'status' => 1, 'data' => ['polNo' => 'P-9', 'polStatus' => 'APPROVED', 'paymentStatus' => 'S']]),
        ]);

        $this->artisan('orders:poll-policies')->expectsOutputToContain('PAYMENT_PENDING -> POLICY_ISSUED')->assertSuccessful();
        $this->assertSame('P-9', $order->fresh()->policy->policy_no);
        $this->assertTrue(collect(app(\Illuminate\Console\Scheduling\Schedule::class)->events())->contains(fn ($e) => str_contains($e->command, 'orders:poll-policies')));
    }

    public function test_checkout_error_is_shown_and_order_stays_payable(): void
    {
        $this->fakeDin($this->dinOffers(), [
            '*/api/v1/Insurance/ChooseScheme' => Http::response(['statusId' => '8010', 'status' => 2, 'message' => ['en' => '8010:active policy exists'], 'data' => []], 406),
        ]);
        $ref = $this->submit($this->form(['repair' => 'garage']))->json('reference');
        $quote = QuoteRequest::where('reference', $ref)->first()->quotes()->first();
        $this->post(route('motor.select', $quote));
        $order = Order::first();

        $this->post(route('motor.checkout.submit', $order), ['name' => 'Omar', 'email' => 'omar@example.com', 'mobile' => '501004000', 'd1' => 'on', 'd2' => 'on', 'd3' => 'on'])
            ->assertRedirect(route('motor.checkout', $order))
            ->assertSessionHasErrors(['checkout' => 'This vehicle already has an active policy.']);
        $this->assertSame('QUOTE_SELECTED', $order->fresh()->state);
        $this->get(route('motor.checkout', $order))->assertSee('This vehicle already has an active policy.');
    }
}

/** Insurer adapter that throws, to prove one insurer cannot break the others. */
class ExplodingAdapter extends \App\Insurers\Awnic\AwnicAdapter
{
    public function quote(\App\Insurers\Data\MotorQuoteInput $input, string $requestId): \App\Insurers\Data\QuoteResult
    {
        throw new \RuntimeException('boom');
    }
}
