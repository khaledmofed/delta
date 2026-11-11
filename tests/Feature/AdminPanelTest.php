<?php

namespace Tests\Feature;

use App\Insurers\InsurerManager;
use App\Models\ApiLog;
use App\Models\AuditLog;
use App\Models\Insurer;
use App\Models\Lead;
use App\Models\Order;
use App\Models\QuoteRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    protected function staff(string $role = 'super_admin', string $email = 'admin@deltains-brokers.com'): User
    {
        return User::create(['name' => 'Test Staff', 'email' => $email, 'role' => $role, 'password' => 'correct-horse-battery']);
    }

    /** A quote request where DIN answered 8013 and AWNIC failed, plus one order with history. */
    protected function journeyData(): array
    {
        $qr = QuoteRequest::create([
            'product' => 'motor',
            'status' => 'NO_QUOTES',
            'input' => ['name_en' => 'Omar Al Mansoori', 'email' => 'omar@example.com', 'mobile' => '971501004000', 'emirates_id' => '784198612345671',
                'emirates_id_expiry' => '2028-05-06', 'make' => 'Nissan', 'model' => 'Altima', 'year' => 2023, 'chassis' => 'TESTCHASSIS000001',
                'cover_type' => 'comprehensive', 'policy_start' => '2026-11-20', 'addons' => ['roadside']],
            'insurer_results' => [
                'din' => ['status' => 'error', 'offers' => 0, 'error_code' => '8013', 'error' => 'Your Emirates ID has expired.', 'message' => 'Your Emirates ID has expired.', 'request_id' => 'aaaa-1111', 'ms' => 4200],
                'awnic' => ['status' => 'error', 'offers' => 0, 'error_code' => 'MAPPING', 'error' => 'No AWNIC NATIONALITY code mapped for Delta/DIN code 102.', 'message' => 'This insurer could not give an online quote right now.', 'request_id' => 'bbbb-2222', 'ms' => 12],
            ],
        ]);
        $quote = $qr->quotes()->create(['insurer' => 'din', 'plan_code' => '1521', 'plan_name' => 'Motor Third Party Liability', 'cover_type' => 'third_party',
            'premium' => 750, 'vat' => 37.5, 'total' => 787.5, 'covers' => [], 'raw' => ['request_id' => 'cccc-3333', 'indicative' => true], 'valid_until' => now()->addDays(7)]);
        $order = Order::create(['quote_request_id' => $qr->id, 'quote_id' => $quote->id, 'insurer' => 'din', 'state' => 'QUOTE_SELECTED',
            'premium' => 750, 'vat' => 37.5, 'total' => 787.5, 'request_id' => 'cccc-3333']);
        $order->transition('QUOTE_SELECTED', 'Offer selected: Motor Third Party Liability', 'customer');
        $order->transition('QUOTE_SELECTED', 'Checkout failed: 8112 Commission code not found');
        $order->transition('PAYMENT_FAILED', 'Payment declined at the insurer');

        return [$qr, $order];
    }

    public function test_guests_are_sent_to_the_staff_login(): void
    {
        $this->get('/admin')->assertRedirect(route('admin.login'));
        $this->get('/admin/orders')->assertRedirect(route('admin.login'));
        $this->get(route('admin.login'))->assertOk()->assertSee('Admin Console')->assertSee('name="password"', false);
    }

    public function test_login_logout_failures_and_throttling(): void
    {
        $this->staff();

        $this->post(route('admin.login.submit'), ['email' => 'admin@deltains-brokers.com', 'password' => 'wrong'])->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->assertTrue(AuditLog::where('action', 'admin.login_failed')->exists());

        $this->post(route('admin.login.submit'), ['email' => 'admin@deltains-brokers.com', 'password' => 'correct-horse-battery'])->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticated();
        $this->assertTrue(AuditLog::where('action', 'admin.login')->exists());
        $this->get(route('admin.dashboard'))->assertOk()->assertSee('Operations overview')->assertSee('Test Staff');

        $this->post(route('admin.logout'))->assertRedirect(route('admin.login'));
        $this->assertGuest();

        // Frozen clock: on a slow run a second could pass and the message would say "59 seconds" (flaky before).
        $this->freezeTime();
        for ($i = 0; $i < 5; $i++) {
            $this->post(route('admin.login.submit'), ['email' => 'admin@deltains-brokers.com', 'password' => 'nope']);
        }
        $this->post(route('admin.login.submit'), ['email' => 'admin@deltains-brokers.com', 'password' => 'correct-horse-battery'])
            ->assertSessionHasErrors(['email' => 'Too many attempts. Try again in 60 seconds.']);
        $this->assertGuest();
    }

    public function test_orders_list_filter_and_state_machine_drawer(): void
    {
        [, $order] = $this->journeyData();
        $this->actingAs($this->staff());

        $this->get(route('admin.orders'))->assertOk()->assertSee($order->reference)->assertSee('Payment failed')->assertSee('AED 787.50');
        $this->get(route('admin.orders', ['state' => 'POLICY_ISSUED']))->assertOk()->assertDontSee($order->reference)->assertSee('No orders yet.');

        $this->get(route('admin.orders.show', $order))->assertOk()
            ->assertSee('class="drawer open"', false)
            ->assertSee('State machine')
            ->assertSee('Checkout failed: 8112 Commission code not found')
            ->assertSee(route('admin.api-logs', ['request_id' => 'cccc-3333']), false);
    }

    public function test_quote_requests_show_each_insurer_result_without_exposing_the_emirates_id(): void
    {
        [$qr] = $this->journeyData();
        $this->actingAs($this->staff());

        $this->get(route('admin.quote-requests'))->assertOk()->assertSee($qr->reference)->assertSee('No quotes');
        $this->get(route('admin.quote-requests.show', $qr))->assertOk()
            ->assertSee('Insurer responses')
            ->assertSee('Error 8013')
            ->assertSee('Your Emirates ID has expired.')
            ->assertSee('No AWNIC NATIONALITY code mapped')      // technical detail is for staff
            ->assertSee('784-1986-•••••••-1')
            ->assertDontSee('784198612345671');
    }

    public function test_api_log_viewer_filters_by_insurer_and_request_id(): void
    {
        $this->actingAs($this->staff());
        $log = fn ($insurer, $op, $rid, $status = 200) => ApiLog::create(['insurer' => $insurer, 'operation' => $op, 'request_id' => $rid, 'method' => 'POST',
            'url' => 'https://uat.example/'.$op, 'request' => ['body' => ['password' => '***']], 'http_status' => $status, 'insurer_status' => '8006', 'response' => ['statusId' => '8006'], 'duration_ms' => 900]);
        $log('din', 'generateQuote', 'rid-777');
        $log('din', 'generateQuote', 'rid-777-A');           // DIN agency variant
        $log('din', 'chooseScheme', 'rid-888', 400);
        $log('awnic', 'calcPremium', 'rid-999');

        $this->get(route('admin.api-logs'))->assertOk()->assertSee('4 calls');
        $this->get(route('admin.api-logs', ['insurer' => 'awnic']))->assertOk()->assertSee('1 calls')->assertSee('rid-999')->assertDontSee('rid-777');
        $this->get(route('admin.api-logs', ['request_id' => 'rid-777']))->assertOk()->assertSee('2 calls')->assertSee('rid-777-A')->assertDontSee('rid-888');
        $this->get(route('admin.api-logs', ['result' => 'failed']))->assertOk()->assertSee('1 calls')->assertSee('rid-888');
        $this->get(route('admin.api-logs', ['request_id' => '%']))->assertOk()->assertSee('0 calls');   // LIKE wildcards are escaped

        $first = ApiLog::where('request_id', 'rid-888')->first();
        $this->get(route('admin.api-logs.show', $first))->assertOk()->assertSee('chooseScheme()')->assertSee('&quot;password&quot;: &quot;***&quot;', false);
    }

    public function test_switching_an_insurer_off_removes_it_from_quoting(): void
    {
        $this->actingAs($this->staff());
        $manager = app(InsurerManager::class);
        $this->assertArrayHasKey('din', $manager->forProduct('motor'));

        $this->post(route('admin.insurers.toggle', 'din'), ['enabled' => '0'])->assertRedirect()->assertSessionHas('toast');
        $this->assertFalse(Insurer::where('code', 'din')->value('enabled'));
        $this->assertArrayNotHasKey('din', $manager->forProduct('motor'));
        $this->assertArrayHasKey('awnic', $manager->forProduct('motor'));
        $this->get('/motor-quote')->assertDontSee('title="Dubai Insurance"', false)->assertDontSee('"code":"din"', false)->assertSee('title="Al Wathba Insurance"', false);
        $this->assertTrue(AuditLog::where('action', 'insurer.disabled')->exists());
        $this->get(route('admin.insurers'))->assertOk()->assertSee('Disabled');

        $this->post(route('admin.insurers.toggle', 'din'), ['enabled' => '1']);
        $this->assertArrayHasKey('din', $manager->forProduct('motor'));

        // An insurer without credentials in .env cannot be switched on from the panel.
        config(['insurers.providers.awnic.enabled' => false]);
        $this->app->forgetInstance(InsurerManager::class);   // the manager reads config once, like after a deploy
        $manager = app(InsurerManager::class);
        $this->post(route('admin.insurers.toggle', 'awnic'), ['enabled' => '1'])->assertSessionHas('toast', fn ($t) => str_contains($t, 'cannot be enabled'));
        $this->assertArrayNotHasKey('awnic', $manager->forProduct('motor'));
        $this->post(route('admin.insurers.toggle', 'nope'), ['enabled' => '1'])->assertNotFound();
    }

    public function test_connection_test_calls_the_insurer(): void
    {
        Http::fake(['*/api/v1/User/Auth' => Http::response(['statusId' => '8029', 'status' => 1, 'data' => str_repeat('t', 385)])]);
        $this->actingAs($this->staff());

        $this->post(route('admin.insurers.test', 'din'))->assertSessionHas('toast', '✔ Dubai Insurance: Token generated (385 chars)');
        $this->assertTrue(AuditLog::where('action', 'insurer.test_connection')->exists());
    }

    public function test_leads_list_and_update(): void
    {
        $staff = $this->staff();
        $lead = Lead::create(['reference' => 'LD-001', 'product' => 'medical', 'name' => 'Sara Ahmed', 'mobile' => '971501112222', 'email' => 'sara@example.com', 'data' => ['message' => 'Family cover for 4']]);
        $this->actingAs($staff);

        $this->get(route('admin.leads'))->assertOk()->assertSee('LD-001')->assertSee('Medical Insurance')->assertSee('Family cover for 4');
        $this->patch(route('admin.leads.update', $lead), ['status' => 'contacted'])->assertRedirect();
        $this->patch(route('admin.leads.update', $lead), ['owner_id' => $staff->id])->assertRedirect();
        $this->assertSame('contacted', $lead->fresh()->status);
        $this->assertSame($staff->id, $lead->fresh()->owner_id);
        $this->patch(route('admin.leads.update', $lead), ['status' => 'hacked'])->assertSessionHasErrors('status');
        $this->assertSame(2, AuditLog::where('action', 'lead.updated')->count());
    }

    public function test_roles_limit_what_staff_can_see_and_change(): void
    {
        [, $order] = $this->journeyData();

        $this->actingAs($this->staff('finance', 'finance@deltains-brokers.com'));
        $this->get(route('admin.orders.show', $order))->assertOk();
        $this->get(route('admin.api-logs'))->assertForbidden();
        $this->get(route('admin.quote-requests'))->assertForbidden();
        $this->get(route('admin.leads'))->assertForbidden();
        $this->get(route('admin.dashboard'))->assertOk()->assertDontSee(route('admin.api-logs'), false)->assertSee(route('admin.orders'), false);

        $this->actingAs($this->staff('readonly', 'ro@deltains-brokers.com'));
        $this->get(route('admin.insurers'))->assertOk()->assertDontSee('Test connection');
        $this->post(route('admin.insurers.toggle', 'din'), ['enabled' => '0'])->assertForbidden();

        $this->actingAs($this->staff('support', 'support@deltains-brokers.com'));
        $lead = Lead::create(['reference' => 'LD-002', 'product' => 'home', 'name' => 'X', 'mobile' => '9715']);
        $this->patch(route('admin.leads.update', $lead), ['status' => 'won'])->assertRedirect();
        $this->get(route('admin.insurers'))->assertForbidden();
    }

    public function test_admin_user_command(): void
    {
        $this->artisan('admin:user', ['email' => 'Ops@Deltains-Brokers.com', '--role' => 'operations', '--generate' => true])
            ->expectsOutputToContain('Created ops@deltains-brokers.com as Operations Manager.')
            ->expectsOutputToContain('Password (shown once):')
            ->assertSuccessful();
        $this->assertSame('operations', User::where('email', 'ops@deltains-brokers.com')->value('role'));

        $this->artisan('admin:user', ['email' => 'x@y.com', '--role' => 'god', '--generate' => true])->assertFailed();
    }
}
