<?php

namespace Tests\Feature;

use App\Mail\LeadReceived;
use App\Mail\NewLeadAlert;
use App\Models\Insurer;
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

    /** Hero as in the prototype (photo, 35+ card, floating card, navy quote bar, logo strip) with real insurer data. */
    public function test_home_hero_matches_the_prototype_with_real_insurers(): void
    {
        $this->get('/')->assertOk()
            ->assertSee('images/photo/hero-advisors.jpg', false)
            ->assertSee('<span class="yr">35+</span>', false)
            ->assertSee('Get motor quotes')->assertSee('Speak to an advisor')
            // floating card: connected insurers, no prices
            ->assertSeeInOrder(['<div class="float-card a">', 'Compare motor quotes', 'title="Dubai Insurance"', 'Dubai Insurance<span class="p">Live quotes</span>', 'title="Al Wathba Insurance"', 'Al Wathba<span class="p">Live quotes</span>'], false)
            ->assertDontSee('Motor quotes · 2024 Toyota Land Cruiser')   // old floating card title with sample prices
            // navy quote bar -> /motor-quote, with the number of enabled insurers
            ->assertSee('<div class="quotebar rv" id="quote">', false)
            ->assertSee('data-quick-quote method="get" action="'.route('motor.quote').'"', false)
            ->assertSee('Quotes from 2 insurers in minutes')
            ->assertSee('Get my quotes')
            // logo strip: live insurers first, then the partners coming soon (order from the insurers table)
            ->assertSeeInOrder(['<div class="insurers insurers--grid">', 'Trusted by leading UAE insurers',
                '<span class="nm">Dubai Insurance</span><span class="ins-tag ins-tag--live">Live</span>',
                '<span class="nm">Al Wathba Insurance</span><span class="ins-tag ins-tag--live">Live</span>',
                '<span class="nm">Sukoon Insurance</span><span class="ins-tag ins-tag--soon">Coming soon</span>',
                '<span class="nm">RAK Insurance</span><span class="ins-tag ins-tag--soon">Coming soon</span>'], false)
            ->assertDontSee('demo — to be confirmed')
            ->assertDontSee('Get your motor insurance quote');   // the hero form card is gone
    }

    public function test_quote_bar_counts_only_enabled_insurers(): void
    {
        Insurer::where('code', 'awnic')->update(['enabled' => false]);   // switched off in the admin panel
        $this->get('/')->assertOk()
            ->assertSee('Quotes from 1 insurer in minutes')
            ->assertSee('<span class="nm">Al Wathba Insurance</span>', false);   // still a partner in the strip

        Insurer::where('code', 'din')->update(['enabled' => false]);
        $this->get('/')->assertOk()->assertSee('Advisor-assisted quotes')->assertDontSee('Quotes from 0');
    }

    public function test_insurer_strip_lists_every_partner_with_live_and_coming_soon(): void
    {
        foreach (['/', route('products.motor')] as $url) {
            $html = $this->get($url)->assertOk()->assertSee('Trusted by leading UAE insurers')->getContent();
            foreach (['Sukoon Insurance', 'Orient Insurance', 'ADNIC', 'GIG Gulf', 'Al Buhaira National Insurance'] as $name) {
                $this->assertStringContainsString('alt="'.$name.'"', $html, "{$name} logo on {$url}");   // logo file exists
            }
            foreach (['Methaq Takaful Insurance' => 'MET', 'Fidelity United' => 'FU', 'RAK Insurance' => 'RAK'] as $name => $mono) {
                $this->assertStringContainsString('<span class="nm">'.$name.'</span>', $html);   // no logo: name + monogram
                $this->assertStringContainsString('d-mono is-fallback" aria-hidden="true">'.$mono.'</span>', $html);
            }
            $this->assertSame(2, substr_count($html, 'ins-tag--live">Live'));
            $this->assertSame(8, substr_count($html, 'ins-tag--soon">Coming soon'));
        }
    }

    public function test_every_insurer_count_on_the_site_follows_live_insurers(): void
    {
        $this->get('/')->assertOk()
            ->assertSee('2 insurers compared')
            ->assertSee('<b>2</b><span>Insurers compared online for motor</span>', false)
            ->assertDontSee('3 insurers')->assertDontSee('<b>3</b>', false);
        $this->get(route('services'))->assertOk()->assertSee('2 insurers compared')->assertDontSee('3 insurers');

        Insurer::where('code', 'awnic')->update(['status' => Insurer::COMING_SOON]);   // from the admin panel
        $this->get('/')->assertOk()->assertSee('1 insurer online')->assertSee('<b>1</b><span>Insurer compared online for motor</span>', false);
        $this->get(route('services'))->assertOk()->assertSee('1 insurer online');

        Insurer::where('code', 'din')->update(['enabled' => false]);   // nothing live can quote
        $this->get('/')->assertOk()->assertSee('Advisor-assisted quotes')->assertDontSee('compared online for motor')->assertDontSee('0 insurer');
        $this->get(route('services'))->assertOk()->assertSee('Advisor-assisted quotes');

        // No fixed insurer count left in any public page source.
        $files = collect(['site', 'partials', 'layouts'])->flatMap(fn ($d) => \Illuminate\Support\Facades\File::allFiles(resource_path("views/{$d}")));
        $this->assertGreaterThan(10, $files->count());
        foreach ($files as $f) {
            $this->assertDoesNotMatchRegularExpression('/\b\d+\s+insurers?\b/i', $f->getContents(), $f->getRelativePathname());
        }
    }

    public function test_insurer_order_and_status_come_from_the_database(): void
    {
        Insurer::where('code', 'rak')->update(['sort' => 0]);
        Insurer::where('code', 'sukoon')->update(['status' => Insurer::LIVE]);   // e.g. connected later: changed in the admin panel

        $this->get('/')->assertOk()
            ->assertSeeInOrder(['<span class="nm">RAK Insurance</span>', '<span class="nm">Dubai Insurance</span>'], false)
            ->assertSee('<span class="nm">Sukoon Insurance</span><span class="ins-tag ins-tag--live">Live</span>', false)
            ->assertSee('Quotes from 2 insurers in minutes');   // only live insurers that can actually quote are counted

        Insurer::where('code', 'awnic')->update(['status' => Insurer::COMING_SOON]);
        $this->get('/')->assertOk()
            ->assertSee('Quotes from 1 insurer in minutes')
            ->assertSee('<span class="nm">Al Wathba Insurance</span><span class="ins-tag ins-tag--soon">Coming soon</span>', false);
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
