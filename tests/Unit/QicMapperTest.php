<?php

namespace Tests\Unit;

use App\Insurers\Data\Offer;
use App\Insurers\Data\PolicyStatus;
use App\Insurers\Qic\MappingMissing;
use App\Insurers\Qic\QicMapper;
use PHPUnit\Framework\TestCase;

/** QIC mapping with test data only (no values from QIC's Postman samples). */
class QicMapperTest extends TestCase
{
    public static function translate(): callable
    {
        return fn (string $type, string $code) => ['nationality' => ['102' => '092'], 'emirate' => ['02' => '002'], 'bank' => []][$type][$code] ?? null;
    }

    public static function vehicle(array $over = []): array
    {
        return ['qic' => $over + ['makeCode' => '0161', 'modelCode' => '20261350', 'vehicleType' => '1002', 'noOfCylinder' => '1004',
            'seatingCapacity' => 5, 'admeId' => '401369', 'sumInsured' => 85000]];
    }

    public function test_tariff_payload_uses_qic_formats_and_codes(): void
    {
        $in = DinMapperTest::input(['extra' => self::vehicle()]);
        $p = QicMapper::tariffPayload($in, self::translate(), '2026-11-26');

        $this->assertSame('0161', $p['makeCode']);
        $this->assertSame(85000.0, $p['sumInsured']);
        $this->assertSame('12/04/1986', $p['driverDOB']);              // DD/MM/YYYY
        $this->assertSame(40, $p['insuredAge']);
        $this->assertSame('092', $p['nationality']);                    // DIN 102 -> QIC 092
        $this->assertSame('002', $p['regnLocation']);                   // DIN emirate 02 -> QIC 002
        $this->assertSame('1001', $p['vehicleUsage']);
        $this->assertSame('1001', $p['geoArea']);
        $this->assertSame('784-1986-1234567-1', $p['civilId']);
        $this->assertSame('401369', $p['admeId']);
        $this->assertStringEndsWith('T00:00:00.000Z', $p['policyFromDate']);
        $this->assertSame('0', $p['financedStatus']);
        $this->assertArrayNotHasKey('financeBank', $p);
        $this->assertArrayNotHasKey('promoCode', $p);                   // empty values are not sent
    }

    public function test_third_party_sends_zero_sum_insured_and_needs_no_trim(): void
    {
        $in = DinMapperTest::input(['cover_type' => 'third_party', 'extra' => self::vehicle(['admeId' => ''])]);
        $p = QicMapper::tariffPayload($in, self::translate(), '2026-11-26');
        $this->assertSame(0.0, $p['sumInsured']);                       // TPL: sumInsured 0, as in QIC's tariff_TP sample
        $this->assertArrayNotHasKey('admeId', $p);
    }

    public function test_missing_vehicle_codes_or_mappings_throw(): void
    {
        foreach ([
            fn () => QicMapper::tariffPayload(DinMapperTest::input(), self::translate()),                                                // no QIC vehicle codes
            fn () => QicMapper::tariffPayload(DinMapperTest::input(['extra' => self::vehicle(['admeId' => ''])]), self::translate()),   // comprehensive needs admeId
            fn () => QicMapper::tariffPayload(DinMapperTest::input(['nationality' => '999', 'extra' => self::vehicle()]), self::translate()),
        ] as $call) {
            try {
                $call();
                $this->fail('MappingMissing expected');
            } catch (MappingMissing $e) {
                $this->assertStringContainsString('QIC', $e->getMessage());
            }
        }
    }

    public static function tariffResponse(): array
    {
        return ['respCode' => '2000', 'errMessage' => null, 'quoteNo' => 'Q-TEST-0001', 'schemes' => [
            ['productCode' => '0110', 'schemeCode' => '0297', 'schemeName' => 'Comprehensive Agency', 'netPremium' => 2100,
                'basicCovers' => [['code' => 'C01', 'name' => 'Own Damage', 'premium' => 1800]],
                'inclusiveCovers' => [['code' => 'C02', 'name' => 'Third Party Liability', 'premium' => 0]],
                'optionalCovers' => [['code' => 'C10', 'name' => 'Rent a Car', 'premium' => 150]],
                'excessCovers' => [['code' => 'X1', 'name' => 'Excess', 'premium' => 500]], 'discountCovers' => []],
            ['productCode' => '0110', 'schemeCode' => '0298', 'schemeName' => 'Comprehensive Non Agency', 'netPremium' => 1575, 'basicCovers' => []],
            ['productCode' => '0120', 'schemeCode' => '0301', 'schemeName' => 'Third Party Liability', 'netPremium' => 787.5, 'basicCovers' => []],
        ]];
    }

    public function test_offers_from_schemes(): void
    {
        $all = QicMapper::offers(self::tariffResponse(), 85000.0, 'rid-1');
        $this->assertCount(3, $all);
        [$agency, $garage, $tpl] = $all;

        $this->assertSame('0110:0297', $agency->planCode);
        $this->assertSame('agency', $agency->repairType);
        $this->assertSame(2100.0, $agency->total);
        $this->assertSame(100.0, $agency->vat);                         // VAT-inclusive netPremium
        $this->assertSame(2000.0, $agency->premium);
        $this->assertSame(500.0, $agency->deductible);
        $this->assertSame('Q-TEST-0001', $agency->insurerRef);
        $this->assertTrue($agency->indicative);
        $this->assertSame(['mandatory', 'mandatory', 'optional'], array_column($agency->covers, 'type'));
        $this->assertSame('garage', $garage->repairType);
        $this->assertSame('third_party', $tpl->coverType);
        $this->assertNull($tpl->sumInsured);

        $this->assertCount(1, QicMapper::offers(self::tariffResponse(), 85000.0, 'rid-1', 'comprehensive', 'agency'));
        $this->assertCount(1, QicMapper::offers(self::tariffResponse(), 85000.0, 'rid-1', 'third_party'));
    }

    public function test_net_premium_payload_and_checkout(): void
    {
        $offer = QicMapper::offers(self::tariffResponse(), 85000.0, 'rid-1')[0];
        $this->assertSame(['quoteNo' => 'Q-TEST-0001', 'schemes' => [['schemeCode' => '0297', 'productCode' => '0110']]], QicMapper::netPremiumPayload($offer, ['NOT-A-COVER']));
        $this->assertSame([['coverCode' => 'C10']], QicMapper::netPremiumPayload($offer, ['C10'])['schemes'][0]['optionalCovers']);

        $ok = QicMapper::checkout('Q-TEST-0001', ['quoteNo' => 'Q-TEST-0001', 'netPremium' => 2257.5, 'taxAmount' => 107.5], ['payLink' => 'https://pay.example/qic/1']);
        $this->assertTrue($ok->ok);
        $this->assertSame('https://pay.example/qic/1', $ok->paymentUrl);
        $this->assertSame([2150.0, 107.5, 2257.5], [$ok->premium, $ok->vat, $ok->total]);

        $bad = QicMapper::checkout('Q-TEST-0001', ['respCode' => '4001', 'errMessage' => 'Invalid scheme'], []);
        $this->assertFalse($bad->ok);
        $this->assertSame(['4001', 'Invalid scheme'], [$bad->errorCode, $bad->errorMessage]);
        $this->assertFalse(QicMapper::checkout('Q-TEST-0001', ['netPremium' => 100], ['errMessage' => 'No link'])->ok);
    }

    public function test_policy_lookup_matches_our_quote_only(): void
    {
        $json = ['policyList' => [
            ['quoteNo' => 'Q-OTHER', 'policyNo' => 'P-OLD', 'activeYn' => 'Y'],
            ['quoteNo' => 'Q-TEST-0001', 'policyNo' => 'P-TEST-0009', 'activeYn' => 'Y', 'premium' => '2257.5', 'civilId' => '784-0000-0000000-0'],
        ]];
        [$st, $row] = QicMapper::policy($json, 'Q-TEST-0001');
        $this->assertSame(PolicyStatus::ISSUED, $st->status);
        $this->assertSame('P-TEST-0009', $st->policyNo);
        $this->assertSame(2257.5, $st->total);
        $this->assertArrayNotHasKey('civilId', $st->raw);               // personal data not kept

        [$pending] = QicMapper::policy(['policyList' => [['quoteNo' => 'Q-OTHER', 'policyNo' => 'P-OLD']]], 'Q-TEST-0001');
        $this->assertSame(PolicyStatus::PENDING, $pending->status);    // never FAILED without an explicit answer
        $this->assertNull($pending->paymentStatus);

        $this->assertSame(['chassisNo' => 'VIN1'], QicMapper::policyLookupPayload(['chassis' => 'VIN1', 'mobile' => '971500000000']));
        $this->assertSame(['mobileNo' => '971500000000'], QicMapper::policyLookupPayload(['chassis' => '', 'mobile' => '971500000000']));
    }

    public function test_success_and_errors(): void
    {
        $this->assertTrue(QicMapper::isOk(['quoteNo' => 'Q1'], 'quoteNo'));
        $this->assertFalse(QicMapper::isOk(['quoteNo' => 'Q1', 'errMessage' => 'Invalid make'], 'quoteNo'));
        $this->assertFalse(QicMapper::isOk([], 'quoteNo'));
        $this->assertFalse(QicMapper::isOk(['respCode' => '2000'], 'quoteNo'));
        $this->assertSame([null, 'QIC request failed'], QicMapper::error([]));
    }
}
