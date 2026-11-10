<?php

namespace App\Console\Commands;

use App\Insurers\Data\MotorQuoteInput;
use App\Insurers\InsurerManager;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class InsurersQuoteTest extends Command
{
    protected $signature = 'insurers:quote-test {insurer} {file : JSON file with the quote input (see database/data/motor-quote.example.json)}';

    protected $description = 'Send one real quote request to an insurer (UAT) and print the normalised offers.';

    public function handle(InsurerManager $insurers): int
    {
        $data = json_decode((string) file_get_contents($this->argument('file')), true);
        if (! is_array($data)) {
            $this->error('Invalid JSON file.');

            return self::FAILURE;
        }
        $input = MotorQuoteInput::fromArray($data);
        $rid = (string) Str::uuid();
        $this->line("X-REQUEST-ID / quote no: {$rid}");
        $res = $insurers->get($this->argument('insurer'))->quote($input, $rid);

        if (! $res->ok) {
            $this->error("Failed: [{$res->errorCode}] {$res->errorMessage}");

            return self::FAILURE;
        }
        $this->table(['Plan', 'Type', 'Repair', 'Sum insured', 'Premium', 'VAT', 'Total', 'Indicative'], array_map(fn ($o) => [
            $o->planName, $o->coverType, $o->repairType ?? '-', $o->sumInsured ?? '-', $o->premium, $o->vat, $o->total, $o->indicative ? 'yes' : 'no',
        ], $res->offers));

        return self::SUCCESS;
    }
}
