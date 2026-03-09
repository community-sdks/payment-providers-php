<?php

declare(strict_types=1);

namespace PaymentProviders\Exceptions;

class ProviderNotFoundException extends ProviderException
{
    public static function forName(string $name): self
    {
        return new self(sprintf('Provider "%s" is not registered.', $name));
    }
}
