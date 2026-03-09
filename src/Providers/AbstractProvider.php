<?php

declare(strict_types=1);

namespace PaymentProviders\Providers;

use PaymentProviders\Contracts\PaymentProviderInterface;
use PaymentProviders\Exceptions\OperationNotSupportedException;

abstract class AbstractProvider implements PaymentProviderInterface
{
    public function getCapabilities(): array
    {
        return [];
    }

    public function createPaymentIntent(array $input): array
    {
        return $this->unsupported(__FUNCTION__);
    }

    public function confirmPayment(array $input): array
    {
        return $this->unsupported(__FUNCTION__);
    }

    public function capturePayment(array $input): array
    {
        return $this->unsupported(__FUNCTION__);
    }

    public function cancelPayment(array $input): array
    {
        return $this->unsupported(__FUNCTION__);
    }

    public function refundPayment(array $input): array
    {
        return $this->unsupported(__FUNCTION__);
    }

    public function createCustomer(array $input): array
    {
        return $this->unsupported(__FUNCTION__);
    }

    public function updateCustomer(array $input): array
    {
        return $this->unsupported(__FUNCTION__);
    }

    public function attachPaymentMethod(array $input): array
    {
        return $this->unsupported(__FUNCTION__);
    }

    public function createSubscription(array $input): array
    {
        return $this->unsupported(__FUNCTION__);
    }

    public function cancelSubscription(array $input): array
    {
        return $this->unsupported(__FUNCTION__);
    }

    public function pauseSubscription(array $input): array
    {
        return $this->unsupported(__FUNCTION__);
    }

    public function resumeSubscription(array $input): array
    {
        return $this->unsupported(__FUNCTION__);
    }

    public function listSubscriptions(array $filters = []): array
    {
        return $this->unsupported(__FUNCTION__);
    }

    public function getInvoice(array $input): array
    {
        return $this->unsupported(__FUNCTION__);
    }

    public function listInvoices(array $filters = []): array
    {
        return $this->unsupported(__FUNCTION__);
    }

    /**
     * @return never
     */
    protected function unsupported(string $operation): array
    {
        throw OperationNotSupportedException::forOperation($this->getName(), $operation);
    }
}
