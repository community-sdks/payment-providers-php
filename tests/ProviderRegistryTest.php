<?php

declare(strict_types=1);

namespace PaymentProviders\Tests;

use PaymentProviders\Exceptions\ProviderNotFoundException;
use PaymentProviders\ProviderRegistry;
use PaymentProviders\Tests\Fixtures\FakeProvider;
use PHPUnit\Framework\TestCase;

final class ProviderRegistryTest extends TestCase
{
    public function testRegisterAndResolveCustomProvider(): void
    {
        $registry = new ProviderRegistry();
        $provider = new FakeProvider();

        $registry->registerProvider('acme', $provider);

        self::assertTrue($registry->has('acme'));
        self::assertSame($provider, $registry->get('acme'));
    }

    public function testFactoryProviderIsLazyAndCached(): void
    {
        $registry = new ProviderRegistry();
        $created = 0;

        $registry->registerFactory('lazy', static function () use (&$created): FakeProvider {
            $created++;

            return new FakeProvider();
        });

        self::assertSame(0, $created);

        $first = $registry->get('lazy');
        $second = $registry->get('lazy');

        self::assertSame(1, $created);
        self::assertSame($first, $second);
    }

    public function testUnknownProviderThrows(): void
    {
        $registry = new ProviderRegistry();

        $this->expectException(ProviderNotFoundException::class);
        $registry->get('missing');
    }
}
