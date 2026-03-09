<?php

declare(strict_types=1);

namespace PaymentProviders\Exceptions;

class OperationNotSupportedException extends ProviderException
{
    public static function forOperation(string $provider, string $operation): self
    {
        return new self(sprintf('Provider "%s" does not support operation "%s".', $provider, $operation));
    }
}
