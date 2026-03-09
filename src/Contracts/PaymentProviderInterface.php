<?php

declare(strict_types=1);

namespace PaymentProviders\Contracts;

interface PaymentProviderInterface
{
    public function getName(): string;

    /** @return array<string, mixed> */
    public function getCapabilities(): array;

    /** @param array<string, mixed> $input @return array<string, mixed> */
    public function createPaymentIntent(array $input): array;

    /** @param array<string, mixed> $input @return array<string, mixed> */
    public function confirmPayment(array $input): array;

    /** @param array<string, mixed> $input @return array<string, mixed> */
    public function capturePayment(array $input): array;

    /** @param array<string, mixed> $input @return array<string, mixed> */
    public function cancelPayment(array $input): array;

    /** @param array<string, mixed> $input @return array<string, mixed> */
    public function refundPayment(array $input): array;

    /** @param array<string, mixed> $input @return array<string, mixed> */
    public function createCustomer(array $input): array;

    /** @param array<string, mixed> $input @return array<string, mixed> */
    public function updateCustomer(array $input): array;

    /** @param array<string, mixed> $input @return array<string, mixed> */
    public function attachPaymentMethod(array $input): array;

    /** @param array<string, mixed> $input @return array<string, mixed> */
    public function createSubscription(array $input): array;

    /** @param array<string, mixed> $input @return array<string, mixed> */
    public function cancelSubscription(array $input): array;

    /** @param array<string, mixed> $input @return array<string, mixed> */
    public function pauseSubscription(array $input): array;

    /** @param array<string, mixed> $input @return array<string, mixed> */
    public function resumeSubscription(array $input): array;

    /** @param array<string, mixed> $filters @return array<string, mixed> */
    public function listSubscriptions(array $filters = []): array;

    /** @param array<string, mixed> $input @return array<string, mixed> */
    public function getInvoice(array $input): array;

    /** @param array<string, mixed> $filters @return array<string, mixed> */
    public function listInvoices(array $filters = []): array;
}
