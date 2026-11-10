<?php

namespace App\Insurers;

use App\Insurers\Contracts\InsurerAdapter;
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

    /** @return array<string, InsurerAdapter> enabled insurers for a product */
    public function forProduct(string $product): array
    {
        $out = [];
        foreach ($this->providers as $code => $cfg) {
            if ($cfg['enabled'] && in_array($product, $cfg['products'] ?? [], true)) {
                $out[$code] = $this->get($code);
            }
        }

        return $out;
    }

    public function codes(): array
    {
        return array_keys($this->providers);
    }
}
