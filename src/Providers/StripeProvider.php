<?php

declare(strict_types=1);

namespace PaymentProviders\Providers;

use PaymentProviders\Exceptions\ProviderException;

class StripeProvider extends AbstractProvider
{
    private array $config;

    private ?object $client = null;

    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    public function getName(): string
    {
        return 'stripe';
    }

    public function getCapabilities(): array
    {
        return [
            'payment_intents' => true,
            'manual_capture' => true,
            'partial_capture' => true,
            'refunds' => true,
            'partial_refunds' => true,
            'customers' => true,
            'saved_payment_methods' => true,
            'subscriptions' => true,
            'pause_subscription' => true,
            'invoices' => true,
            'webhooks' => true,
            'idempotency_keys' => true,
            'metadata' => true,
        ];
    }

    public function createPaymentIntent(array $input): array
    {
        $params = [
            'amount' => (int) ($input['amount']['amount'] ?? 0),
            'currency' => (string) ($input['amount']['currency'] ?? 'usd'),
            'customer' => $input['customer_id'] ?? null,
            'payment_method' => $input['payment_method_id'] ?? null,
            'capture_method' => $input['capture_method'] ?? 'automatic',
            'description' => $input['description'] ?? null,
            'metadata' => $input['metadata'] ?? null,
        ];

        $intent = $this->client()->paymentIntents->create($this->withoutNull($params), $this->requestOptions($input));

        return ['payment_intent' => $this->toArray($intent)];
    }

    public function confirmPayment(array $input): array
    {
        $intent = $this->client()->paymentIntents->confirm(
            (string) $input['payment_intent_id'],
            $this->withoutNull(['payment_method' => $input['payment_method_id'] ?? null]),
            $this->requestOptions($input)
        );

        return ['payment_intent' => $this->toArray($intent), 'payment' => null];
    }

    public function capturePayment(array $input): array
    {
        $params = [];
        if (isset($input['amount_to_capture']['amount'])) {
            $params['amount_to_capture'] = (int) $input['amount_to_capture']['amount'];
        }

        $intent = $this->client()->paymentIntents->capture(
            (string) $input['payment_intent_id'],
            $params,
            $this->requestOptions($input)
        );

        return ['payment_intent' => $this->toArray($intent), 'payment' => null];
    }

    public function cancelPayment(array $input): array
    {
        $intent = $this->client()->paymentIntents->cancel(
            (string) $input['payment_intent_id'],
            $this->withoutNull(['cancellation_reason' => $input['reason'] ?? null]),
            $this->requestOptions($input)
        );

        return ['payment_intent' => $this->toArray($intent)];
    }

    public function refundPayment(array $input): array
    {
        $params = [
            'payment_intent' => $input['payment_intent_id'] ?? null,
            'charge' => $input['payment_id'] ?? null,
            'amount' => $input['amount']['amount'] ?? null,
            'reason' => $input['reason'] ?? null,
        ];

        $refund = $this->client()->refunds->create($this->withoutNull($params), $this->requestOptions($input));

        return ['refund' => $this->toArray($refund), 'payment' => null];
    }

    public function createCustomer(array $input): array
    {
        $customer = $this->client()->customers->create(
            $this->withoutNull([
                'email' => $input['email'] ?? null,
                'name' => $input['name'] ?? null,
                'phone' => $input['phone'] ?? null,
                'metadata' => $input['metadata'] ?? null,
            ]),
            $this->requestOptions($input)
        );

        return ['customer' => $this->toArray($customer)];
    }

    public function updateCustomer(array $input): array
    {
        $customer = $this->client()->customers->update(
            (string) $input['customer_id'],
            $this->withoutNull([
                'email' => $input['email'] ?? null,
                'name' => $input['name'] ?? null,
                'phone' => $input['phone'] ?? null,
                'metadata' => $input['metadata'] ?? null,
                'invoice_settings' => isset($input['default_payment_method_id'])
                    ? ['default_payment_method' => $input['default_payment_method_id']]
                    : null,
            ]),
            $this->requestOptions($input)
        );

        return ['customer' => $this->toArray($customer)];
    }

    public function attachPaymentMethod(array $input): array
    {
        $paymentMethod = $this->client()->paymentMethods->attach(
            (string) $input['payment_method_id'],
            ['customer' => (string) $input['customer_id']],
            $this->requestOptions($input)
        );

        if (!empty($input['set_as_default'])) {
            $this->client()->customers->update(
                (string) $input['customer_id'],
                ['invoice_settings' => ['default_payment_method' => (string) $input['payment_method_id']]],
                $this->requestOptions($input)
            );
        }

        return ['payment_method' => $this->toArray($paymentMethod)];
    }

    public function createSubscription(array $input): array
    {
        $subscription = $this->client()->subscriptions->create(
            $this->withoutNull([
                'customer' => $input['customer_id'] ?? null,
                'items' => [['price' => $input['plan_id'] ?? null]],
                'default_payment_method' => $input['default_payment_method_id'] ?? null,
                'metadata' => $input['metadata'] ?? null,
            ]),
            $this->requestOptions($input)
        );

        return ['subscription' => $this->toArray($subscription), 'latest_invoice' => null];
    }

    public function cancelSubscription(array $input): array
    {
        if (!empty($input['cancel_at_period_end'])) {
            $subscription = $this->client()->subscriptions->update(
                (string) $input['subscription_id'],
                ['cancel_at_period_end' => true],
                $this->requestOptions($input)
            );
        } else {
            $subscription = $this->client()->subscriptions->cancel(
                (string) $input['subscription_id'],
                [],
                $this->requestOptions($input)
            );
        }

        return ['subscription' => $this->toArray($subscription)];
    }

    public function pauseSubscription(array $input): array
    {
        $subscription = $this->client()->subscriptions->update(
            (string) $input['subscription_id'],
            ['pause_collection' => ['behavior' => 'void']],
            $this->requestOptions($input)
        );

        return ['subscription' => $this->toArray($subscription)];
    }

    public function resumeSubscription(array $input): array
    {
        $subscription = $this->client()->subscriptions->update(
            (string) $input['subscription_id'],
            ['pause_collection' => ''],
            $this->requestOptions($input)
        );

        return ['subscription' => $this->toArray($subscription)];
    }

    public function listSubscriptions(array $filters = []): array
    {
        $params = $this->withoutNull([
            'customer' => $filters['customer_id'] ?? null,
            'status' => $filters['status'] ?? null,
            'limit' => $filters['limit'] ?? null,
            'starting_after' => $filters['starting_after'] ?? null,
        ]);

        $result = $this->client()->subscriptions->all($params);
        $array = $this->toArray($result);

        return [
            'data' => $array['data'] ?? [],
            'has_more' => (bool) ($array['has_more'] ?? false),
            'next_cursor' => null,
        ];
    }

    public function getInvoice(array $input): array
    {
        $invoice = $this->client()->invoices->retrieve((string) $input['invoice_id']);

        return ['invoice' => $this->toArray($invoice)];
    }

    public function listInvoices(array $filters = []): array
    {
        $params = $this->withoutNull([
            'customer' => $filters['customer_id'] ?? null,
            'subscription' => $filters['subscription_id'] ?? null,
            'status' => $filters['status'] ?? null,
            'limit' => $filters['limit'] ?? null,
            'starting_after' => $filters['starting_after'] ?? null,
        ]);

        $result = $this->client()->invoices->all($params);
        $array = $this->toArray($result);

        return [
            'data' => $array['data'] ?? [],
            'has_more' => (bool) ($array['has_more'] ?? false),
            'next_cursor' => null,
        ];
    }

    private function client(): object
    {
        if ($this->client instanceof \Stripe\StripeClient) {
            return $this->client;
        }

        if (!class_exists('\Stripe\\StripeClient')) {
            throw new ProviderException('stripe/stripe-php is not installed. Run composer install.');
        }

        $apiKey = (string) ($this->config['api_key'] ?? '');
        if ($apiKey === '') {
            throw new ProviderException('Stripe provider requires config key "api_key".');
        }

        $this->client = new \Stripe\StripeClient($apiKey);

        return $this->client;
    }

    private function requestOptions(array $input): array
    {
        if (!isset($input['idempotency_key']) || $input['idempotency_key'] === null || $input['idempotency_key'] === '') {
            return [];
        }

        return ['idempotency_key' => (string) $input['idempotency_key']];
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

        if (is_object($value) && method_exists($value, 'toArray')) {
            /** @var array<string, mixed> $array */
            $array = $value->toArray();

            return $array;
        }

        if (is_object($value)) {
            /** @var array<string, mixed> $array */
            $array = get_object_vars($value);

            return $array;
        }

        return [];
    }
}
