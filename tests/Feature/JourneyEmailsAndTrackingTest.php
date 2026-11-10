<?php

namespace Tests\Feature;

use App\Mail\Journey\OrderAwaitingPayment;
use App\Mail\Journey\PolicyIssued;
use App\Mail\Journey\QuoteRequestReceived;
use App\Mail\Journey\TeamOrderAlert;
use App\Mail\Journey\TeamPolicyIssued;
use App\Models\Order;
use App\Models\QuoteRequest;
use App\Services\MotorOrders;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\Feature\Concerns\BuildsMotorJourney;
use Tests\TestCase;

/** Motor journey emails (customer + Delta team) and "Track my order". */
class JourneyEmailsAndTrackingTest extends TestCase
{
    use BuildsMotorJourney, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpMotorJourney();
        config(['site.ops_email' => 'ops@deltains-brokers.com']);
    }

    protected function issuedPaymentInfo(bool $withDocument = true): array
    {
        return ['*/api/v1/Insurance/GetPaymentInfo' => Http::response(['statusId' => '8200', 'status' => 1, 'data' => array_filter([
            'polNo' => 'P/01/2026/000555', 'polStatus' => 'APPROVED', 'paymentStatus' => 'S', 'payable' => 1575,
            'documents' => $withDocument ? base64_encode('%PDF-1.4 policy schedule '.str_repeat('x', 400)) : null,
        ])])];
    }

    /** Email link as the customer would click it (relative, so it hits the test app). */
    protected function linkIn(string $html, string $label): string
    {
        $this->assertMatchesRegularExpression('#<a href="([^"]+)"[^>]*>'.preg_quote($label, '#').'</a>#', $html);
        preg_match('#<a href="([^"]+)"[^>]*>'.preg_quote($label, '#').'</a>#', $html, $m);

        return html_entity_decode($m[1]);
    }

    public function test_quote_request_email_links_to_the_results_from_any_browser(): void
    {
        Mail::fake();
        $this->fakeDin($this->dinOffers());
        $ref = $this->submit($this->form())->assertOk()->json('reference');
        $qr = QuoteRequest::where('reference', $ref)->first();

        $mail = null;
        Mail::assertQueued(QuoteRequestReceived::class, function ($m) use ($qr, &$mail) {
            $mail = $m;

            return $m->hasTo('omar@example.com') && $m->qr->is($qr);
        });
        $html = $mail->render();
        $this->assertStringContainsString($ref, $html);
        $this->assertStringContainsString('2023 Toyota Camry', str_replace((string) now()->subYears(3)->year, '2023', $html));
        $link = $this->linkIn($html, 'View your offers');

        // Another browser: no session -> the signed link opens the results.
        $this->flushSession();
        $this->get(route('motor.results', $qr))->assertNotFound();
        $this->get($link)->assertRedirect(route('motor.results', $qr));
        $this->get(route('motor.results', $qr))->assertOk();

        // Tampered or expired link -> friendly message, no access.
        $this->flushSession();
        $this->get($link.'x')->assertRedirect(route('motor.quote'))->assertSessionHas('notice');
        $this->travel(8)->days();
        $this->get($link)->assertRedirect(route('motor.quote'));
        $this->get(route('motor.results', $qr))->assertNotFound();
    }

    public function test_order_confirmation_email_before_payment_is_sent_once(): void
    {
        Mail::fake();
        [$order] = $this->orderAwaitingPayment(['*/api/v1/Insurance/GetPaymentInfo' => Http::response(['statusId' => '8203', 'status' => 2, 'data' => []], 406)]);

        Mail::assertQueued(OrderAwaitingPayment::class, 1);
        $mail = null;
        Mail::assertQueued(OrderAwaitingPayment::class, function ($m) use ($order, &$mail) {
            $mail = $m;

            return $m->hasTo('omar@example.com') && $m->order->is($order);
        });
        $html = $mail->render();
        $this->assertStringContainsString($order->reference, $html);
        $this->assertStringContainsString('AED 1,575.00', $html);
        $this->assertStringContainsString('Dubai Insurance', $html);

        // Paying again reuses the open payment page: no second email.
        $this->post(route('motor.pay', $order))->assertRedirect('https://pay.example/1');
        Mail::assertQueued(OrderAwaitingPayment::class, 1);

        // "Complete payment" opens the payment page in another browser.
        $link = $this->linkIn($html, 'Complete payment');
        $this->flushSession();
        $this->get($link)->assertRedirect(route('motor.payment', $order));
        $this->get(route('motor.payment', $order))->assertOk()->assertSee('Pay AED 1,575.00');
    }

    public function test_policy_issued_emails_and_document_download(): void
    {
        Mail::fake();
        [$order] = $this->orderAwaitingPayment($this->issuedPaymentInfo());
        $this->get('/motor/payment/return/success')->assertRedirect(route('motor.confirmation', $order));
        $order->refresh();
        $this->assertSame('POLICY_ISSUED', $order->state);

        $mail = null;
        Mail::assertQueued(PolicyIssued::class, function ($m) use (&$mail) {
            $mail = $m;

            return $m->hasTo('omar@example.com');
        });
        Mail::assertQueued(TeamPolicyIssued::class, fn ($m) => $m->hasTo('ops@deltains-brokers.com') && str_contains($m->render(), route('admin.orders.show', $order)));
        $html = $mail->render();
        $this->assertStringContainsString('P/01/2026/000555', $html);
        $mail->assertHasSubject('You’re insured — policy P/01/2026/000555');

        // Confirmation page shows the download button; the file comes from the private disk as a download.
        $this->get(route('motor.confirmation', $order))->assertOk()->assertSee('Policy schedule')->assertSee(route('motor.policy.document', $order), false);
        $res = $this->get(route('motor.policy.document', $order))->assertOk();
        $this->assertStringContainsString('attachment', $res->headers->get('content-disposition'));
        $this->assertStringContainsString('Policy-p01202600055', $res->headers->get('content-disposition'));
        $this->assertSame('nosniff', $res->headers->get('x-content-type-options'));
        $this->assertStringStartsWith('%PDF-1.4 policy schedule', $res->streamedContent());

        // Email link downloads it from another browser; without it the file is not reachable.
        $link = $this->linkIn($html, 'Download policy document');
        $this->flushSession();
        $this->get(route('motor.policy.document', $order))->assertNotFound();
        $this->get($link)->assertRedirect(route('motor.policy.document', $order));
        $this->get(route('motor.policy.document', $order))->assertOk();
    }

    public function test_policy_without_a_document_links_to_the_policy_page(): void
    {
        Mail::fake();
        [$order] = $this->orderAwaitingPayment($this->issuedPaymentInfo(false));
        $this->artisan('orders:poll-policies')->assertSuccessful();

        Mail::assertQueued(PolicyIssued::class, fn ($m) => str_contains($m->render(), 'View your policy') && ! str_contains($m->render(), 'Download policy document'));
        $this->get(route('motor.confirmation', $order))->assertOk()->assertSee('sent to you separately')->assertDontSee('fa-file-arrow-down', false);
        $this->get(route('motor.policy.document', $order))->assertNotFound();
    }

    public function test_team_is_alerted_when_the_insurer_refuses_checkout_or_payment_fails(): void
    {
        Mail::fake();
        $this->fakeDin($this->dinOffers(), [
            '*/api/v1/Insurance/ChooseScheme' => Http::response(['statusId' => '8112', 'status' => 0, 'message' => ['en' => '8112:Unable to process request Commission code not found'], 'data' => null], 400),
        ]);
        $ref = $this->submit($this->form(['repair' => 'garage']))->json('reference');
        $this->post(route('motor.select', QuoteRequest::where('reference', $ref)->first()->quotes()->first()));
        $order = Order::first();
        $this->post(route('motor.checkout.submit', $order), ['name' => 'Omar', 'email' => 'omar@example.com', 'mobile' => '501004000', 'd1' => 'on', 'd2' => 'on', 'd3' => 'on'])
            ->assertSessionHasErrors('checkout');

        Mail::assertQueued(TeamOrderAlert::class, fn ($m) => $m->hasTo('ops@deltains-brokers.com') && $m->what === 'Insurer refused checkout' && str_contains($m->detail, '8112'));
        Mail::assertNotQueued(OrderAwaitingPayment::class);
        $this->assertStringContainsString('Commission code not found', Mail::queued(TeamOrderAlert::class)->first()->render());
    }

    public function test_team_is_alerted_on_declined_payment(): void
    {
        Mail::fake();
        [$order] = $this->orderAwaitingPayment(['*/api/v1/Insurance/GetPaymentInfo' => Http::response(['statusId' => '8200', 'status' => 1, 'data' => ['polStatus' => 'Pending', 'paymentStatus' => 'F']])]);
        $this->artisan('orders:poll-policies');

        $this->assertSame('PAYMENT_FAILED', $order->fresh()->state);
        Mail::assertQueued(TeamOrderAlert::class, fn ($m) => $m->what === 'Payment declined' && $m->order->is($order));
        Mail::assertNotQueued(PolicyIssued::class);
    }

    public function test_track_my_order(): void
    {
        Mail::fake();
        [$order] = $this->orderAwaitingPayment($this->issuedPaymentInfo());
        $this->artisan('orders:poll-policies');
        $this->flushSession();
        $this->flushHeaders();   // a normal browser form post, not the wizard's JSON request

        $this->get(route('track'))->assertOk()->assertSee('Track my order')->assertSee('name="reference"', false);
        $this->get(route('motor.confirmation', $order))->assertNotFound();

        // Wrong details and unknown orders get the same answer.
        $this->post(route('track.lookup'), ['reference' => $order->reference, 'contact' => 'someone@else.com'])->assertSessionHasErrors(['reference' => 'We couldn’t find an order with these details. Please check the order number and the email or mobile you used.']);
        $this->post(route('track.lookup'), ['reference' => 'ORD-2026-NOPE0000', 'contact' => 'omar@example.com'])->assertSessionHasErrors(['reference' => 'We couldn’t find an order with these details. Please check the order number and the email or mobile you used.']);
        $this->get(route('motor.confirmation', $order))->assertNotFound();

        // Email (any case) or mobile (any common format) + lower-case reference.
        foreach (['OMAR@example.com', '+971 50 100 4000', '0501004000', '50 100 4000'] as $contact) {
            $this->flushSession();
            $this->post(route('track.lookup'), ['reference' => strtolower($order->reference), 'contact' => $contact])->assertRedirect(route('motor.confirmation', $order));
        }
        $this->get(route('motor.confirmation', $order))->assertOk()->assertSee('You’re insured!')->assertSee('P/01/2026/000555')->assertSee('Policy schedule');
        $this->get(route('motor.policy.document', $order))->assertOk();

        // "Sign in" / "My Account" in the header now lead here.
        $this->get('/')->assertSee('href="'.url('/track').'"', false);
    }

    public function test_track_is_rate_limited(): void
    {
        [$order] = $this->orderAwaitingPayment($this->issuedPaymentInfo());
        $this->flushHeaders();
        for ($i = 0; $i < 5; $i++) {
            $this->post(route('track.lookup'), ['reference' => $order->reference, 'contact' => "guess{$i}@x.com"]);
        }
        $this->post(route('track.lookup'), ['reference' => $order->reference, 'contact' => 'omar@example.com'])
            ->assertSessionHasErrors(['reference' => 'Too many attempts. Please try again in 15 minutes or call us on +971 6 574 7420.']);
    }

    public function test_expired_order_link_goes_to_track_with_the_reference(): void
    {
        Mail::fake();
        [$order] = $this->orderAwaitingPayment($this->issuedPaymentInfo());
        $this->artisan('orders:poll-policies');
        $link = $this->linkIn(Mail::queued(PolicyIssued::class)->first()->render(), 'Download policy document');

        $this->flushSession();
        $this->travel(31)->days();
        $this->get($link)->assertRedirect(route('track', ['ref' => $order->reference]))->assertSessionHas('notice');
        $this->get(route('track', ['ref' => $order->reference]))->assertSee('value="'.$order->reference.'"', false);
    }

    public function test_an_order_being_checked_elsewhere_is_not_processed_twice(): void
    {
        Mail::fake();
        [$order] = $this->orderAwaitingPayment($this->issuedPaymentInfo());

        $lock = Cache::lock("order:{$order->id}:refresh", 60);
        $this->assertTrue($lock->get());   // e.g. the scheduler is processing this order right now
        app(MotorOrders::class)->refresh($order);
        $this->assertSame('PAYMENT_PENDING', $order->fresh()->state);
        Http::assertNotSent(fn ($r) => str_contains($r->url(), 'GetPaymentInfo'));
        $lock->release();

        app(MotorOrders::class)->refresh($order);
        app(MotorOrders::class)->refresh($order->fresh());   // already issued: nothing more happens
        $this->assertSame('POLICY_ISSUED', $order->fresh()->state);
        Mail::assertQueued(PolicyIssued::class, 1);
        Mail::assertQueued(TeamPolicyIssued::class, 1);
    }

    public function test_a_mail_failure_never_blocks_the_journey(): void
    {
        $this->fakeDin($this->dinOffers());
        Mail::shouldReceive('to')->andThrow(new \RuntimeException('SMTP down'));

        $this->submit($this->form())->assertOk();
        $this->assertSame('QUOTES_RECEIVED', QuoteRequest::first()->status);
    }
}
