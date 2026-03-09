# payment-providers/contracts-php

PHP package implementing provider-agnostic payment contracts defined in `../contracts`.

## Features

- Unified provider interface aligned with shared contracts.
- Built-in providers included in package:
  - Stripe (`stripe/stripe-php`)
  - Paddle (`paddlehq/paddle-php-sdk`)
  - PayPal (`paypal/paypal-server-sdk`)
- Register custom providers outside of this package.
- PHPUnit tests for registry, gateway routing, and custom provider registration.

## Install

```bash
composer install
```

## Quick Start

```php
<?php

use PaymentProviders\PaymentGateway;
use PaymentProviders\ProviderRegistry;

$registry = ProviderRegistry::createWithBuiltins([
    'stripe' => ['api_key' => 'sk_test_xxx'],
    'paddle' => ['api_key' => 'pdl_xxx'],
    'paypal' => [
        'client_id' => 'client_id',
        'client_secret' => 'client_secret',
        'environment' => 'sandbox',
    ],
]);

$gateway = new PaymentGateway($registry);
$provider = $gateway->provider('stripe');

$intent = $provider->createPaymentIntent([
    'amount' => ['amount' => 1099, 'currency' => 'usd'],
    'capture_method' => 'automatic',
    'idempotency_key' => 'order-1001',
]);
```

## Register Custom Provider

```php
<?php

use PaymentProviders\ProviderRegistry;
use YourCompany\Payments\AcmePayProvider;

$registry = ProviderRegistry::createWithBuiltins();
$registry->registerProvider('acmepay', new AcmePayProvider());
```
