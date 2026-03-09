<?php

declare(strict_types=1);

namespace PaymentProviders\Tests\Fixtures;

use PaymentProviders\Providers\AbstractProvider;

class FakeProvider extends AbstractProvider
{
    public function getName(): string
    {
        return 'fake';
    }

    public function getCapabilities(): array
    {
        return [
            'payment_intents' => true,
            'subscriptions' => false,
        ];
    }

    public function createPaymentIntent(array $input): array
    {
        return [
            'payment_intent' => [
                'id' => 'pi_fake_123',
                'status' => 'requires_confirmation',
                'amount' => $input['amount'] ?? null,
            ],
        ];
    }
}
