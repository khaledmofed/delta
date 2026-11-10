<?php

namespace Tests\Feature;

use App\Mail\LeadReceived;
use App\Mail\NewLeadAlert;
use App\Models\InsurerCode;
use App\Models\Lead;
use App\Models\Quote;
use App\Models\QuoteRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\Feature\Concerns\BuildsMotorJourney;
use Tests\TestCase;

/** Delta_Visual_Website_Improvement_Examples: hero quick quote, plan details page, Claims & Support. */
class SiteImprovementsTest extends TestCase
{
    use BuildsMotorJourney, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpMotorJourney();
    }

    public function test_home_hero_has_the_quick_motor_quote_form(): void
    {
        InsurerCode::create(['insurer' => 'din', 'type' => 'plateSource', 'code' => '0001', 'label' => 'Dubai']);

        $this->get('/')->assertOk()
            ->assertSee('Get your motor insurance quote')
            ->assertSee('data-quick-quote method="get" action="'.route('motor.quote').'"', false)
            ->assertSee('name="make"', false)->assertSee('name="model"', false)->assertSee('name="year"', false)
            ->assertSee('<option value="0001">Dubai</option><option value="3000">plateSource 3000</option>', false)
            ->assertSee('Continue to quotes')
            ->assertSee('Get motor quotes')->assertSee('Speak to an advisor')
            ->assertDontSee('hero-advisors.jpg')         // the old hero photo card with sample prices
            ->assertDontSee('class="quotebar', false);
    }

    public function test_hero_hides_the_emirate_field_when_codes_are_missing(): void
    {
        InsurerCode::where('type', 'plateSource')->delete();
        $this->get('/')->assertOk()->assertSee('Continue to quotes')->assertDontSee('name="plateSource"', false);
    }

    public function test_results_offer_a_plan_details_link_and_the_page_shows_every_cover(): void
    {
        $this->fakeDin($this->dinOffers());
        $ref = $this->submit($this->form(['repair' => 'garage']))->assertOk()->json('reference');
        $qr = QuoteRequest::where('reference', $ref)->first();
        $quote = $qr->quotes()->first();

        $status = $this->getJson(route('motor.status', $qr))->assertOk();
        $url = $status->json('quotes.0.detailsUrl');
        $this->assertSame(route('motor.plan', ['quoteRequest' => $qr, 'quote' => $quote]), $url);

        $this->get($url)->assertOk()
            ->assertSee('Comprehensive Gold (Garage)')
            ->assertSee('Coverage summary')
            ->assertSee('Own damage')->assertSee('Included')
            ->assertSee('Roadside Assistance')->assertSee('Optional — added (you asked for it)')   // roadside add-on from the wizard
            ->assertSee('Rent a Car')->assertSee('Optional — not included')->assertSee('+AED 250.00')
            ->assertSee('Garage repair')
            ->assertSee('Confirmed by the insurer before payment')                               // DIN gives the excess at checkout
            ->assertSee('Key terms')->assertSee('Indicative price')->assertSee('12 months')
            ->assertSee('AED 1,680')                                                              // (1500 + 100) × 1.05
            ->assertSee('action="'.route('motor.select', $quote).'"', false)
            ->assertSee('Continue with this plan');
    }

    public function test_plan_details_are_private_and_scoped_to_their_quote_request(): void
    {
        $this->fakeDin($this->dinOffers());
        $ref = $this->submit($this->form(['repair' => 'garage']))->json('reference');
        $qr = QuoteRequest::where('reference', $ref)->first();
        $quote = $qr->quotes()->first();
        $other = QuoteRequest::create(['product' => 'motor', 'input' => [], 'status' => 'QUOTES_RECEIVED']);
        $otherQuote = Quote::create(['quote_request_id' => $other->id, 'insurer' => 'din', 'plan_code' => 'X', 'plan_name' => 'Other', 'cover_type' => 'third_party', 'premium' => 1, 'vat' => 0, 'total' => 1]);

        $this->get(route('motor.plan', ['quoteRequest' => $qr, 'quote' => $otherQuote]))->assertNotFound();

        $this->travel(8)->days();
        $this->get(route('motor.plan', ['quoteRequest' => $qr, 'quote' => $quote]))->assertOk()->assertSee('This offer has expired')->assertDontSee('Continue with this plan');

        $this->flushSession();
        $this->get(route('motor.plan', ['quoteRequest' => $qr, 'quote' => $quote]))->assertNotFound();
    }

    public function test_claims_and_support_page(): void
    {
        $this->get('/')->assertSee('href="'.route('claims').'">Claims &amp; Support</a>', false);
        $this->get(route('claims'))->assertOk()
            ->assertSee('<li class="active"><a href="'.route('claims').'">', false)
            ->assertSee('How to make a claim')->assertSee('Get a police report')
            ->assertSee('href="tel:999"', false)->assertSee('href="tel:998"', false)->assertSee('href="tel:997"', false)
            ->assertSee('Documents you usually need')
            ->assertSee('we connect you with your insurer’s claims team')    // no insurer hotline configured
            ->assertSee('name="requestType"', false)->assertSee('name="policyNo"', false)
            ->assertSee('name="source" value="claims"', false);

        // An insurer hotline appears only once it is configured.
        config(['insurers.providers.din.claims_phone' => '800 4444']);
        $this->get(route('claims'))->assertSee('href="tel:8004444"', false)->assertDontSee('we connect you with your insurer’s claims team');
    }

    public function test_claims_help_request_is_saved_as_a_lead_with_emails(): void
    {
        Mail::fake();
        $form = ['source' => 'claims', 'product' => 'claims', 'name' => 'Huda Saleh', 'mobile' => '0552223333', 'email' => 'huda@example.com',
            'requestType' => 'new_claim', 'policyNo' => 'p/01/2026/000555', 'insurer' => 'din', 'message' => 'Rear-ended at a signal in Al Nahda, police report done.', 'consent' => 'on'];

        $this->postJson(route('leads.store'), array_merge($form, ['requestType' => '', 'message' => '']))
            ->assertStatus(422)->assertJsonValidationErrors(['requestType', 'message']);
        $this->postJson(route('leads.store'), array_merge($form, ['requestType' => 'refund_now', 'insurer' => 'acme']))
            ->assertStatus(422)->assertJsonValidationErrors(['requestType', 'insurer']);

        $this->postJson(route('leads.store'), $form)->assertOk();
        $lead = Lead::firstOrFail();
        $this->assertSame('claims', $lead->product);
        $this->assertSame('Claims & Support', $lead->productName());
        $this->assertSame(['Request type' => 'Report a new claim', 'Policy number' => 'P/01/2026/000555', 'Insurer' => 'Dubai Insurance'], $lead->supportDetails());

        Mail::assertQueued(LeadReceived::class, fn ($m) => str_contains($m->render(), 'Report a new claim'));
        Mail::assertQueued(NewLeadAlert::class, fn ($m) => str_contains($m->render(), 'P/01/2026/000555') && str_contains($m->render(), 'Claims &amp; Support page'));

        $this->actingAs(User::create(['name' => 'Support', 'email' => 'support@deltains-brokers.com', 'role' => 'support', 'password' => 'secret-password-1']));
        $this->get(route('admin.leads', ['product' => 'claims']))->assertOk()
            ->assertSee($lead->reference)->assertSee('Claims &amp; Support', false)->assertSee('Report a new claim · P/01/2026/000555 · Dubai Insurance');
    }
}
