<?php

declare(strict_types=1);

namespace PaymentProviders\Providers;

use PaymentProviders\Exceptions\ProviderException;

class PayPalProvider extends AbstractProvider
{
    private array $config;

    private ?object $client = null;

    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    public function getName(): string
    {
        return 'paypal';
    }

    public function getCapabilities(): array
    {
        return [
            'payment_intents' => true,
            'manual_capture' => true,
            'partial_capture' => false,
            'refunds' => true,
            'partial_refunds' => true,
            'customers' => false,
            'saved_payment_methods' => true,
            'subscriptions' => true,
            'pause_subscription' => false,
            'invoices' => false,
            'webhooks' => true,
            'idempotency_keys' => true,
            'metadata' => true,
        ];
    }

    public function createPaymentIntent(array $input): array
    {
        $controller = $this->client()->getOrdersController();
        $payload = [
            'intent' => ($input['capture_method'] ?? 'automatic') === 'manual' ? 'AUTHORIZE' : 'CAPTURE',
            'purchase_units' => [[
                'amount' => [
                    'currency_code' => strtoupper((string) ($input['amount']['currency'] ?? 'USD')),
                    'value' => $this->toDecimal((int) ($input['amount']['amount'] ?? 0)),
                ],
            ]],
        ];

        $result = $controller->ordersCreate(['body' => $payload]);

        return ['payment_intent' => $this->toArray($result)];
    }

    public function confirmPayment(array $input): array
    {
        $controller = $this->client()->getOrdersController();
        $result = $controller->ordersCapture(['id' => (string) $input['payment_intent_id']]);

        return ['payment_intent' => $this->toArray($result), 'payment' => null];
    }

    public function capturePayment(array $input): array
    {
        $controller = $this->client()->getOrdersController();
        $result = $controller->ordersCapture(['id' => (string) $input['payment_intent_id']]);

        return ['payment_intent' => $this->toArray($result), 'payment' => null];
    }

    public function refundPayment(array $input): array
    {
        $controller = $this->client()->getPaymentsController();
        $captureId = (string) ($input['payment_id'] ?? '');
        if ($captureId === '') {
            throw new ProviderException('PayPal refundPayment requires "payment_id" as capture id.');
        }

        $payload = [];
        if (isset($input['amount']['amount'], $input['amount']['currency'])) {
            $payload['amount'] = [
                'currency_code' => strtoupper((string) $input['amount']['currency']),
                'value' => $this->toDecimal((int) $input['amount']['amount']),
            ];
        }

        $result = $controller->capturesRefund(['captureId' => $captureId, 'body' => $payload]);

        return ['refund' => $this->toArray($result), 'payment' => null];
    }

    public function createSubscription(array $input): array
    {
        $controller = $this->client()->getSubscriptionsController();

        $payload = [
            'plan_id' => (string) ($input['plan_id'] ?? ''),
            'custom_id' => $input['metadata']['reference'] ?? null,
        ];

        $result = $controller->subscriptionsCreate(['body' => $this->withoutNull($payload)]);

        return ['subscription' => $this->toArray($result), 'latest_invoice' => null];
    }

    public function cancelSubscription(array $input): array
    {
        $controller = $this->client()->getSubscriptionsController();
        $controller->subscriptionsCancel(['id' => (string) $input['subscription_id'], 'body' => ['reason' => 'Canceled by integrator']]);

        return ['subscription' => ['id' => (string) $input['subscription_id'], 'status' => 'canceled']];
    }

    public function listSubscriptions(array $filters = []): array
    {
        $controller = $this->client()->getSubscriptionsController();

        $result = $controller->subscriptionsGet((string) ($filters['subscription_id'] ?? ''));
        $data = $this->toArray($result);

        return ['data' => empty($data) ? [] : [$data], 'has_more' => false, 'next_cursor' => null];
    }

    private function client(): object
    {
        if ($this->client !== null) {
            return $this->client;
        }

        if (!class_exists('\PaypalServerSdkLib\\PaypalServerSdkClientBuilder')) {
            throw new ProviderException('paypal/paypal-server-sdk is not installed. Run composer install.');
        }

        $clientId = (string) ($this->config['client_id'] ?? '');
        $clientSecret = (string) ($this->config['client_secret'] ?? '');

        if ($clientId === '' || $clientSecret === '') {
            throw new ProviderException('PayPal provider requires "client_id" and "client_secret".');
        }

        $environment = strtolower((string) ($this->config['environment'] ?? 'sandbox'));
        $envConst = $environment === 'production'
            ? \PaypalServerSdkLib\Environment::PRODUCTION
            : \PaypalServerSdkLib\Environment::SANDBOX;

        $this->client = \PaypalServerSdkLib\PaypalServerSdkClientBuilder::init()
            ->clientCredentialsAuthCredentials(
                \PaypalServerSdkLib\Authentication\ClientCredentialsAuthCredentialsBuilder::init($clientId, $clientSecret)
            )
            ->environment($envConst)
            ->build();

        return $this->client;
    }

    private function toDecimal(int $minorAmount): string
    {
        return number_format($minorAmount / 100, 2, '.', '');
    }

    /** @param array<string, mixed> $input @return array<string, mixed> */
    private function withoutNull(array $input): array
    {
        return array_filter($input, static fn ($value): bool => $value !== null);
    }

    /** @return array<string, mixed> */
    private function toArray(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (is_object($value) && method_exists($value, 'getResult')) {
            $result = $value->getResult();
            if (is_array($result)) {
                return $result;
            }
            if (is_object($result)) {
                /** @var array<string, mixed> $array */
                $array = get_object_vars($result);

                return $array;
            }
        }

        if (is_object($value)) {
            /** @var array<string, mixed> $array */
            $array = get_object_vars($value);

            return $array;
        }

        return [];
    }
}
