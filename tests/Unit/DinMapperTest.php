<?php

namespace Tests\Unit;

use App\Insurers\Data\MotorQuoteInput;
use App\Insurers\Din\DinMapper;
use PHPUnit\Framework\TestCase;

class DinMapperTest extends TestCase
{
    public static function input(array $over = []): MotorQuoteInput
    {
        $a = json_decode(file_get_contents(__DIR__.'/../../database/data/motor-quote.example.json'), true);

        return MotorQuoteInput::fromArray(array_merge($a, $over));
    }

    public function test_quote_payload_matches_spec_format(): void
    {
        $p = DinMapper::quotePayload(self::input(), false);
        $this->assertSame('784198612345671', $p['nationalId']);
        $this->assertSame('06/05/2028', $p['idExpiryDt']);
        $this->assertSame('12/04/1986', $p['dateOfBirth']);
        $this->assertSame('971501234567', $p['mobileNumber']);
        $this->assertSame('0001', $p['plateSource']);
        $this->assertSame('N', $p['agencyRepairYn']);
        $this->assertArrayNotHasKey('bankName', $p);
        $this->assertSame('103', $p['documentLists'][0]['code']);
        $this->assertSame('png', $p['documentLists'][0]['type']);
    }

    public function test_repair_variants(): void
    {
        $this->assertSame([false, true], DinMapper::repairVariants(self::input()));
        $this->assertSame([true], DinMapper::repairVariants(self::input(['repair' => 'agency'])));
        $this->assertSame([false], DinMapper::repairVariants(self::input(['cover_type' => 'third_party'])));
    }

    public function test_offers_and_scheme(): void
    {
        $json = ['status' => 1, 'statusId' => '8006', 'data' => [
            ['prodCode' => '1001', 'prodName' => ['en' => '1001 - Comprehensive Gold'], 'sumInsured' => 244871, 'covers' => [
                'mandatory' => [['coverCode' => '10001', 'coverName' => ['en' => 'Loss or Damage'], 'premium' => 4409.6], ['coverCode' => '15001', 'coverName' => ['en' => 'TP Bodily Injury'], 'premium' => 1100]],
                'optional' => [['coverCode' => '10008', 'coverName' => ['en' => 'Rent A Car'], 'premium' => 140]],
            ]],
            ['prodCode' => '1521', 'prodName' => ['en' => '1521 - Motor Third Party Liability'], 'sumInsured' => 0, 'covers' => [
                'mandatory' => [['coverCode' => '15001', 'coverName' => ['en' => 'TP Bodily Injury'], 'premium' => 1100]], 'optional' => null,
            ]],
        ]];
        $all = DinMapper::offers($json, true, 'rid-1');
        $this->assertCount(2, $all);
        $this->assertSame('Comprehensive Gold (Agency)', $all[0]->planName);
        $this->assertSame(5509.6, $all[0]->premium);
        $this->assertSame(275.48, $all[0]->vat);
        $this->assertSame('third_party', $all[1]->coverType);
        $this->assertCount(1, DinMapper::offers($json, true, 'rid-1', 'comprehensive'));

        $scheme = DinMapper::chooseSchemePayload($all[0], ['10008', '99999']);
        $this->assertSame(['prodCode' => '1001', 'covers' => ['mandatory' => '10001,15001', 'optional' => '10008']], $scheme);
    }

    public function test_checkout_and_policy_parsing(): void
    {
        $c = DinMapper::checkout(['status' => 1, 'data' => ['quotationNo' => 'PQ/1', 'netPremium' => 1100, 'vat' => 55, 'netToCustomer' => 1155, 'excess' => 0, 'paymentUrl' => 'https://pay']]);
        $this->assertTrue($c->ok);
        $this->assertSame(1155.0, $c->total);

        $p = DinMapper::policy(['status' => 1, 'statusId' => '8201', 'data' => ['polNo' => 'P/1', 'polStatus' => 'APPROVED', 'paymentStatus' => 'S', 'payable' => '2052.75', 'documents' => 'PGh0bWw+']]);
        $this->assertSame('issued', $p->status);
        $this->assertSame('P/1', $p->policyNo);

        $pending = DinMapper::policy(['status' => 0, 'statusId' => '8203', 'message' => ['en' => 'not completed'], 'data' => null]);
        $this->assertSame('pending', $pending->status);

        [$code, $msg] = DinMapper::error(['status' => 0, 'statusId' => '8032', 'message' => ['en' => 'Missing'], 'data' => 'NationalId, Gender']);
        $this->assertSame('8032', $code);
        $this->assertStringContainsString('NationalId', $msg);
    }
}
