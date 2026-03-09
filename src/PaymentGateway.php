<?php

declare(strict_types=1);

namespace PaymentProviders;

use PaymentProviders\Contracts\PaymentProviderInterface;

class PaymentGateway
{
    public function __construct(private readonly ProviderRegistry $registry)
    {
    }

    public function provider(string $name): PaymentProviderInterface
    {
        return $this->registry->get($name);
    }
}
