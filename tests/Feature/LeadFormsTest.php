<?php

namespace Tests\Feature;

use App\Mail\LeadReceived;
use App\Mail\NewLeadAlert;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\SendQueuedMailable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/** Website lead forms (product pages + contact) -> leads table -> queued emails -> admin panel. */
class LeadFormsTest extends TestCase
{
    use RefreshDatabase;

    protected function form(array $overrides = []): array
    {
        return array_replace([
            'source' => 'product', 'name' => 'Sara Ahmed', 'mobile' => '50 111 2222', 'email' => 'Sara@Example.com',
            'product' => 'medical', 'message' => 'Family of 4, ages 38, 35, 8 and 5.', 'contactPref' => 'WhatsApp', 'emirate' => 'Dubai',
            'consent' => 'on', 'company_website' => '',
        ], $overrides);
    }

    public static function formPages(): array
    {
        return [
            'contact' => ['/contact', 'contact'], 'medical' => ['/medical-insurance', 'product'], 'home' => ['/home-insurance', 'product'],
            'travel' => ['/travel-insurance', 'product'], 'boat' => ['/boat-insurance', 'product'], 'pet' => ['/pet-insurance', 'product'],
        ];
    }

    #[DataProvider('formPages')]
    public function test_every_lead_form_posts_to_the_backend(string $url, string $source): void
    {
        $this->get($url)->assertOk()
            ->assertSee('data-lead-form method="post" action="'.route('leads.store').'"', false)
            ->assertSee('name="_token"', false)
            ->assertSee('name="source" value="'.$source.'"', false)
            ->assertSee('name="company_website"', false);
    }

    public function test_lead_is_saved_and_both_emails_are_queued(): void
    {
        Mail::fake();
        config(['site.leads_email' => 'leads@deltains-brokers.com']);

        $res = $this->withHeader('referer', url('/medical-insurance'))->postJson(route('leads.store'), $this->form())
            ->assertOk()->assertJsonStructure(['reference', 'name'])->assertJsonPath('name', 'Sara');

        $lead = Lead::firstOrFail();
        $this->assertSame($res->json('reference'), $lead->reference);
        $this->assertMatchesRegularExpression('/^LD-\d{6}-[A-Z0-9]{5}$/', $lead->reference);
        $this->assertSame('971501112222', $lead->mobile);
        $this->assertSame('sara@example.com', $lead->email);
        $this->assertSame('new', $lead->status);
        $this->assertSame(['message' => 'Family of 4, ages 38, 35, 8 and 5.', 'contact_pref' => 'WhatsApp', 'emirate' => 'Dubai', 'source' => 'product', 'page' => '/medical-insurance'], $lead->data);

        Mail::assertQueued(LeadReceived::class, fn ($m) => $m->hasTo('sara@example.com') && $m->lead->is($lead));
        Mail::assertQueued(NewLeadAlert::class, fn ($m) => $m->hasTo('leads@deltains-brokers.com') && $m->hasReplyTo('sara@example.com'));
        Mail::assertQueuedCount(2);
    }

    public function test_emails_go_through_the_queue(): void
    {
        Queue::fake();
        $this->postJson(route('leads.store'), $this->form())->assertOk();

        Queue::assertPushed(SendQueuedMailable::class, 2);
        Queue::assertPushed(SendQueuedMailable::class, fn ($job) => $job->mailable instanceof LeadReceived);
        Queue::assertPushed(SendQueuedMailable::class, fn ($job) => $job->mailable instanceof NewLeadAlert);
    }

    public function test_email_content(): void
    {
        $lead = Lead::create(['product' => 'travel', 'name' => 'Omar Al Mansoori', 'email' => 'omar@example.com', 'mobile' => '971501004000',
            'data' => ['message' => "Two travellers\n<script>alert(1)</script>", 'contact_pref' => 'Email', 'emirate' => 'Sharjah', 'source' => 'product', 'page' => '/travel-insurance']]);

        $customer = (new LeadReceived($lead))->render();
        $this->assertStringContainsString('Thank you, Omar.', $customer);
        $this->assertStringContainsString($lead->reference, $customer);
        $this->assertStringContainsString('Travel Insurance', $customer);
        $this->assertStringContainsString('contact you by email', $customer);
        $this->assertStringContainsString('&lt;script&gt;', $customer);              // customer text is escaped
        $this->assertStringNotContainsString('<script>alert', $customer);
        $this->assertStringContainsString('delta-logo-horizontal-white.png', $customer);
        (new LeadReceived($lead))->assertHasSubject("We received your request — {$lead->reference}");

        $team = (new NewLeadAlert($lead))->render();
        $this->assertStringContainsString('Omar Al Mansoori', $team);
        $this->assertStringContainsString('+971501004000', $team);
        $this->assertStringContainsString('Product page (/travel-insurance)', $team);
        $this->assertStringContainsString(route('admin.leads', ['status' => 'new']), $team);
        (new NewLeadAlert($lead))->assertHasSubject("New lead — Travel Insurance — Omar Al Mansoori ({$lead->reference})");
    }

    public function test_validation(): void
    {
        Mail::fake();
        $this->postJson(route('leads.store'), $this->form(['name' => '', 'mobile' => '12345', 'email' => 'nope', 'consent' => null]))
            ->assertStatus(422)->assertJsonValidationErrors(['name', 'mobile', 'email', 'consent']);
        $this->postJson(route('leads.store'), $this->form(['product' => 'crypto', 'emirate' => 'Mars', 'contactPref' => 'Fax', 'source' => 'x']))
            ->assertStatus(422)->assertJsonValidationErrors(['product', 'emirate', 'contactPref', 'source']);

        // Contact page: the message is required; product pages: optional.
        $this->postJson(route('leads.store'), $this->form(['source' => 'contact', 'product' => 'general', 'message' => '']))
            ->assertStatus(422)->assertJsonValidationErrors(['message' => 'Please write your message.']);
        $this->postJson(route('leads.store'), $this->form(['message' => '', 'contactPref' => null, 'emirate' => null]))->assertOk();
        $this->postJson(route('leads.store'), $this->form(['source' => 'contact', 'product' => 'general', 'mobile' => '0501112222']))->assertOk();

        $this->assertSame(2, Lead::count());
        $this->assertSame('General enquiry', Lead::where('product', 'general')->first()->productName());
        Mail::assertQueuedCount(4);
    }

    public function test_honeypot_looks_successful_but_saves_nothing(): void
    {
        Mail::fake();
        $this->postJson(route('leads.store'), $this->form(['company_website' => 'http://spam.example']))->assertOk()->assertJsonStructure(['reference']);

        $this->assertSame(0, Lead::count());
        Mail::assertNothingQueued();
    }

    public function test_without_javascript_the_form_returns_to_the_page_with_a_confirmation(): void
    {
        Mail::fake();
        $this->from('/pet-insurance')->post(route('leads.store'), $this->form(['product' => 'pet']))
            ->assertRedirect('/pet-insurance')->assertSessionHas('lead_reference', Lead::first()->reference);
        $this->get('/pet-insurance')->assertSee('window.DELTA_LEAD_SENT = "'.Lead::first()->reference.'"', false);

        $this->from('/contact')->post(route('leads.store'), $this->form(['source' => 'contact', 'message' => '']))
            ->assertRedirect('/contact')->assertSessionHasErrors('message');
    }

    public function test_a_mail_failure_never_loses_the_lead(): void
    {
        Mail::shouldReceive('to')->andThrow(new \RuntimeException('SMTP down'));

        $this->postJson(route('leads.store'), $this->form())->assertOk();
        $this->assertSame(1, Lead::count());
    }

    public function test_lead_forms_are_rate_limited(): void
    {
        Mail::fake();
        for ($i = 0; $i < 5; $i++) {
            $this->postJson(route('leads.store'), $this->form())->assertOk();
        }
        $this->postJson(route('leads.store'), $this->form())->assertStatus(429);
        $this->assertSame(5, Lead::count());
    }

    public function test_new_leads_appear_in_the_admin_panel(): void
    {
        Mail::fake();
        $this->postJson(route('leads.store'), $this->form(['product' => 'boat', 'message' => '32 ft yacht, Dubai Marina']))->assertOk();
        $lead = Lead::first();

        $this->actingAs(User::create(['name' => 'Ops', 'email' => 'ops@deltains-brokers.com', 'role' => 'operations', 'password' => 'secret-password-1']));
        $this->get(route('admin.leads', ['status' => 'new']))->assertOk()
            ->assertSee($lead->reference)->assertSee('Boat Insurance')->assertSee('32 ft yacht, Dubai Marina')->assertSee('WhatsApp · Dubai · Product page');
        $this->get(route('admin.dashboard'))->assertSee('New lead — Boat · Sara Ahmed');
    }
}
