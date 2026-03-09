<?php

declare(strict_types=1);

namespace PaymentProviders;

use PaymentProviders\Contracts\PaymentProviderInterface;
use PaymentProviders\Exceptions\ProviderNotFoundException;
use PaymentProviders\Providers\PaddleProvider;
use PaymentProviders\Providers\PayPalProvider;
use PaymentProviders\Providers\StripeProvider;

class ProviderRegistry
{
    /** @var array<string, PaymentProviderInterface> */
    private array $providers = [];

    /** @var array<string, callable(): PaymentProviderInterface> */
    private array $factories = [];

    public static function createWithBuiltins(array $config = []): self
    {
        $registry = new self();

        $registry->registerFactory('stripe', static fn (): PaymentProviderInterface => new StripeProvider($config['stripe'] ?? []));
        $registry->registerFactory('paddle', static fn (): PaymentProviderInterface => new PaddleProvider($config['paddle'] ?? []));
        $registry->registerFactory('paypal', static fn (): PaymentProviderInterface => new PayPalProvider($config['paypal'] ?? []));

        return $registry;
    }

    public function registerProvider(string $name, PaymentProviderInterface $provider): void
    {
        $this->providers[strtolower($name)] = $provider;
    }

    /** @param callable(): PaymentProviderInterface $factory */
    public function registerFactory(string $name, callable $factory): void
    {
        $this->factories[strtolower($name)] = $factory;
    }

    public function has(string $name): bool
    {
        $normalized = strtolower($name);

        return isset($this->providers[$normalized]) || isset($this->factories[$normalized]);
    }

    public function get(string $name): PaymentProviderInterface
    {
        $normalized = strtolower($name);

        if (isset($this->providers[$normalized])) {
            return $this->providers[$normalized];
        }

        if (isset($this->factories[$normalized])) {
            $provider = ($this->factories[$normalized])();
            $this->providers[$normalized] = $provider;

            return $provider;
        }

        throw ProviderNotFoundException::forName($name);
    }

    /** @return list<string> */
    public function names(): array
    {
        $names = array_unique(array_merge(array_keys($this->providers), array_keys($this->factories)));
        sort($names);

        return array_values($names);
    }
}
