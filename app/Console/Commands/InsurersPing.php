<?php

namespace App\Console\Commands;

use App\Insurers\InsurerManager;
use Illuminate\Console\Command;

class InsurersPing extends Command
{
    protected $signature = 'insurers:ping {insurer? : din | awnic (default: all)}';

    protected $description = 'Check connectivity and credentials for insurer APIs (run this after the server IP is whitelisted).';

    public function handle(InsurerManager $insurers): int
    {
        $codes = $this->argument('insurer') ? [$this->argument('insurer')] : $insurers->codes();
        $fail = false;
        foreach ($codes as $code) {
            $r = $insurers->get($code)->ping();
            $this->line(sprintf('%-6s %s  %s', $code, $r['ok'] ? '<info>OK</info>  ' : '<error>FAIL</error>', $r['message']));
            $fail = $fail || ! $r['ok'];
        }

        return $fail ? self::FAILURE : self::SUCCESS;
    }
}
