<?php

namespace Tests\Feature\Concerns;

use App\Models\InsurerCode;
use App\Models\Order;
use App\Models\QuoteRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

/** Shared helpers for motor journey feature tests: DIN codes, a valid wizard form, faked DIN API. */
trait BuildsMotorJourney
{
    protected function setUpMotorJourney(): void
    {
        Storage::fake('local');
        config(['site.demo' => false]);

        // Only the DIN codes this form uses (the full DinCodesSeeder is slow).
        $codes = ['gender' => ['M'], 'nationality' => ['102'], 'emirate' => ['02'], 'ncdYears' => ['3'],
            'trafficTranType' => ['103'], 'plateSource' => ['3000'], 'plateCode' => ['3'], 'bank' => ['600']];
        foreach ($codes as $type => $list) {
            foreach ($list as $code) {
                InsurerCode::create(['insurer' => 'din', 'type' => $type, 'code' => $code, 'label' => "{$type} {$code}"]);
            }
        }
    }

    /** A valid wizard submission (field names as in motor-quote.html). */
    protected function form(array $overrides = []): array
    {
        $file = fn ($n) => UploadedFile::fake()->image("{$n}.jpg", 40, 40);

        return array_replace([
            'name' => 'Omar Al Mansoori', 'nameAr' => '', 'email' => 'omar@example.com', 'mobile' => '50 100 4000',
            'dob' => now()->subYears(40)->toDateString(), 'gender' => 'M', 'nationality' => '102', 'emirate' => '02',
            'emiratesId' => '784-1986-1234567-1', 'eidExpiry' => now()->addYears(2)->toDateString(),
            'licenseNo' => '1234567', 'ncdYears' => '3', 'licenseFrom' => now()->subYears(10)->toDateString(), 'licenseTo' => now()->addYears(3)->toDateString(),
            'trafficTranType' => '103', 'isNew' => 'no', 'chassis' => 'jtmhv05j604123456', 'tcfNumber' => '10234567',
            'make' => 'Toyota', 'model' => 'Camry', 'year' => (string) now()->subYears(3)->year, 'gccSpec' => 'yes', 'ev' => 'no',
            'plateSource' => '3000', 'plateCode' => '3', 'regNumber' => '45120', 'regDt' => now()->subYear()->toDateString(), 'financed' => 'no',
            'coverType' => 'comprehensive', 'repair' => 'any', 'roadside' => 'on', 'policyStart' => now()->addDay()->toDateString(),
            'consentShare' => 'on', 'consentTerms' => 'on',
            'documents' => [
                'eid_front' => $file('eid_front'), 'eid_back' => $file('eid_back'), 'licence_front' => $file('licence_front'),
                'licence_back' => $file('licence_back'), 'mulkiya_front' => $file('mulkiya_front'), 'mulkiya_back' => $file('mulkiya_back'),
            ],
        ], $overrides);
    }

    protected function fakeDin(array $generateQuote, array $extra = []): void
    {
        Http::fake($extra + [
            '*/api/v1/User/Auth' => Http::response(['statusId' => '8029', 'status' => 1, 'data' => 'jwt-token']),
            '*/api/v1/Insurance/GenerateQuote' => Http::response(...$generateQuote),
            // AWNIC has no code mappings in tests -> it fails before any HTTP call; this is a safety net.
            '*/motor/*' => Http::response(['isSuccess' => 'N', 'errorList' => [['code' => 'E1', 'desc' => 'IP not whitelisted']]]),
        ]);
    }

    protected function dinOffers(): array
    {
        return [['statusId' => '8006', 'status' => 1, 'data' => [[
            'prodCode' => '1001', 'prodName' => ['en' => '1001 - Comprehensive Gold'], 'sumInsured' => 85000,
            'covers' => [
                'mandatory' => [['coverCode' => '10001', 'coverName' => ['en' => 'Own damage'], 'premium' => 1500]],
                'optional' => [['coverCode' => '20001', 'coverName' => ['en' => 'Roadside Assistance'], 'premium' => 100], ['coverCode' => '20002', 'coverName' => ['en' => 'Rent a Car'], 'premium' => 250]],
            ],
        ]]]];
    }

    protected function submit(array $form)
    {
        return $this->withHeader('Accept', 'application/json')->post(route('motor.quote.store'), $form);
    }

    /** Runs the journey up to the insurer payment page. */
    protected function orderAwaitingPayment(array $paymentInfo): array
    {
        $this->fakeDin($this->dinOffers(), $paymentInfo + [
            '*/api/v1/Insurance/ChooseScheme' => Http::response(['statusId' => '8007', 'status' => 1, 'data' => ['paymentUrl' => 'https://pay.example/1', 'netPremium' => 1500, 'vat' => 75, 'netToCustomer' => 1575]]),
        ]);
        $ref = $this->submit($this->form(['repair' => 'garage', 'roadside' => null]))->json('reference');
        $quote = QuoteRequest::where('reference', $ref)->first()->quotes()->first();
        $this->post(route('motor.select', $quote));
        $order = Order::first();
        $this->post(route('motor.checkout.submit', $order), ['name' => 'Omar', 'email' => 'omar@example.com', 'mobile' => '501004000', 'd1' => 'on', 'd2' => 'on', 'd3' => 'on'])
            ->assertRedirect('https://pay.example/1');

        return [$order->fresh()];
    }
}
