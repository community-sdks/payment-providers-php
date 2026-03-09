<?php

declare(strict_types=1);

namespace PaymentProviders\Tests;

use PaymentProviders\ProviderRegistry;
use PHPUnit\Framework\TestCase;

final class BuiltinProvidersTest extends TestCase
{
    public function testBuiltinsAreAutoRegistered(): void
    {
        $registry = ProviderRegistry::createWithBuiltins();

        $names = $registry->names();

        self::assertContains('stripe', $names);
        self::assertContains('paddle', $names);
        self::assertContains('paypal', $names);
    }

    public function testBuiltinCanBeOverriddenByCustomProvider(): void
    {
        $registry = ProviderRegistry::createWithBuiltins();
        $custom = new Fixtures\FakeProvider();

        $registry->registerProvider('stripe', $custom);

        self::assertSame($custom, $registry->get('stripe'));
    }
}
