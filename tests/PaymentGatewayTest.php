<?php

declare(strict_types=1);

namespace PaymentProviders\Tests;

use PaymentProviders\PaymentGateway;
use PaymentProviders\ProviderRegistry;
use PaymentProviders\Tests\Fixtures\FakeProvider;
use PHPUnit\Framework\TestCase;

final class PaymentGatewayTest extends TestCase
{
    public function testGatewayResolvesProviderAndRunsOperation(): void
    {
        $registry = new ProviderRegistry();
        $registry->registerProvider('fake', new FakeProvider());

        $gateway = new PaymentGateway($registry);
        $provider = $gateway->provider('fake');

        $result = $provider->createPaymentIntent([
            'amount' => ['amount' => 2500, 'currency' => 'usd'],
        ]);

        self::assertSame('pi_fake_123', $result['payment_intent']['id']);
        self::assertSame(2500, $result['payment_intent']['amount']['amount']);
    }
}
