<?php

namespace Tests\Unit;

use App\Insurers\Awnic\AwnicMapper;
use App\Insurers\Awnic\MappingMissing;
use PHPUnit\Framework\TestCase;

class AwnicMapperTest extends TestCase
{
    private function translate(): callable
    {
        return fn (string $type, string $code) => ['nationality' => ['102' => '1086'], 'emirate' => ['02' => '02']][$type][$code] ?? null;
    }

    public function test_premium_payload(): void
    {
        $in = DinMapperTest::input(['extra' => ['awnic' => ['makeCode' => 'BY200013', 'modelCode' => 'BY300001', 'specDesc' => '120i', 'sumInsured' => 112943]]]);
        $p = AwnicMapper::premiumPayload($in, $this->translate());
        $this->assertSame('12-APR-1986', $p['dob']);
        $this->assertSame('1086', $p['nationality']);
        $this->assertSame(2, $p['noClaimYear']);
        $this->assertSame(112943.0, $p['sumInsured']);
    }

    public function test_missing_vehicle_or_code_mapping_throws(): void
    {
        $this->expectException(MappingMissing::class);
        AwnicMapper::premiumPayload(DinMapperTest::input(), $this->translate());
    }

    public function test_offers_parsing(): void
    {
        $json = ['isSuccess' => 'Y', 'premiumInfo' => [
            ['planName' => 'AWNIC Comprehensive Agency', 'finalPremiumAmt' => '2000', 'finalPremiumVat' => '100', 'deductibleAmt' => '500', 'covers' => [['coverId' => '1_27_6', 'coverDesc' => 'PAB', 'premium' => '120', 'optedYN' => 'N']]],
            ['planName' => 'AWNIC Comprehensive Non Agency', 'finalPremiumAmt' => '1500', 'finalPremiumVat' => '75', 'deductibleAmt' => '350', 'covers' => []],
            ['planName' => 'AWNIC Third Party', 'finalPremiumAmt' => '900', 'finalPremiumVat' => '45', 'covers' => []],
        ]];
        $o = AwnicMapper::offers($json, 100000, 'q1');
        $this->assertCount(3, $o);
        $this->assertSame('agency', $o[0]->repairType);
        $this->assertSame('garage', $o[1]->repairType);
        $this->assertSame('third_party', $o[2]->coverType);
        $this->assertSame(2100.0, $o[0]->total);
        $this->assertSame('optional', $o[0]->covers[0]['type']);
        $this->assertCount(1, AwnicMapper::offers($json, 100000, 'q1', 'comprehensive', 'garage'));
    }
}
