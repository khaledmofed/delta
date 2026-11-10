<?php

namespace App\Insurers;

use App\Insurers\Contracts\InsurerAdapter;
use App\Models\Insurer;
use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;

class InsurerManager
{
    /** @var array<string, InsurerAdapter> */
    protected array $resolved = [];

    public function __construct(protected Container $app, protected array $providers) {}

    public function get(string $code): InsurerAdapter
    {
        if (! isset($this->providers[$code])) {
            throw new InvalidArgumentException("Unknown insurer [{$code}].");
        }

        return $this->resolved[$code] ??= new ($this->providers[$code]['adapter'])($this->providers[$code]);
    }

    /**
     * Enabled insurers for a product. An insurer must be enabled in config (.env: credentials present)
     * AND not switched off in the admin panel (insurers.enabled). No row in `insurers` = config decides.
     *
     * @return array<string, InsurerAdapter>
     */
    public function forProduct(string $product): array
    {
        $off = $this->switchedOff();
        $out = [];
        foreach ($this->providers as $code => $cfg) {
            if ($cfg['enabled'] && ! in_array($code, $off, true) && in_array($product, $cfg['products'] ?? [], true)) {
                $out[$code] = $this->get($code);
            }
        }

        return $out;
    }

    /** Whether .env enables the insurer (the admin switch cannot turn on an insurer without credentials). */
    public function configured(string $code): bool
    {
        return (bool) ($this->providers[$code]['enabled'] ?? false);
    }

    /** @return list<string> insurer codes switched off in the admin panel */
    protected function switchedOff(): array
    {
        try {
            return Insurer::where('enabled', false)->pluck('code')->all();
        } catch (\Throwable) {
            return [];   // e.g. before migrations
        }
    }

    public function codes(): array
    {
        return array_keys($this->providers);
    }
}
