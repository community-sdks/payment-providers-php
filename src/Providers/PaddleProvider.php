<?php

declare(strict_types=1);

namespace PaymentProviders\Providers;

use PaymentProviders\Exceptions\ProviderException;

class PaddleProvider extends AbstractProvider
{
    private array $config;

    private ?object $client = null;

    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    public function getName(): string
    {
        return 'paddle';
    }

    public function getCapabilities(): array
    {
        return [
            'payment_intents' => false,
            'manual_capture' => false,
            'partial_capture' => false,
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

    public function createCustomer(array $input): array
    {
        $payload = $this->withoutNull([
            'email' => $input['email'] ?? null,
            'name' => $input['name'] ?? null,
            'custom_data' => $input['metadata'] ?? null,
        ]);

        $customer = $this->client()->customers->create($payload);

        return ['customer' => $this->toArray($customer)];
    }

    public function updateCustomer(array $input): array
    {
        $payload = $this->withoutNull([
            'email' => $input['email'] ?? null,
            'name' => $input['name'] ?? null,
            'custom_data' => $input['metadata'] ?? null,
        ]);

        $customer = $this->client()->customers->update((string) $input['customer_id'], $payload);

        return ['customer' => $this->toArray($customer)];
    }

    public function createSubscription(array $input): array
    {
        $payload = $this->withoutNull([
            'customer_id' => $input['customer_id'] ?? null,
            'items' => [['price_id' => $input['plan_id'] ?? null, 'quantity' => 1]],
            'custom_data' => $input['metadata'] ?? null,
        ]);

        $subscription = $this->client()->subscriptions->create($payload);

        return ['subscription' => $this->toArray($subscription), 'latest_invoice' => null];
    }

    public function cancelSubscription(array $input): array
    {
        $subscription = $this->client()->subscriptions->cancel((string) $input['subscription_id']);

        return ['subscription' => $this->toArray($subscription)];
    }

    public function pauseSubscription(array $input): array
    {
        $subscription = $this->client()->subscriptions->pause((string) $input['subscription_id']);

        return ['subscription' => $this->toArray($subscription)];
    }

    public function resumeSubscription(array $input): array
    {
        $subscription = $this->client()->subscriptions->resume((string) $input['subscription_id']);

        return ['subscription' => $this->toArray($subscription)];
    }

    public function listSubscriptions(array $filters = []): array
    {
        $result = $this->client()->subscriptions->list($this->withoutNull([
            'customer_id' => $filters['customer_id'] ?? null,
            'status' => $filters['status'] ?? null,
            'per_page' => $filters['limit'] ?? null,
            'after' => $filters['starting_after'] ?? null,
        ]));

        $array = $this->toArray($result);

        return [
            'data' => $array['data'] ?? [],
            'has_more' => (bool) ($array['meta']['has_more'] ?? false),
            'next_cursor' => $array['meta']['next'] ?? null,
        ];
    }

    public function getInvoice(array $input): array
    {
        $invoice = $this->client()->transactions->get((string) $input['invoice_id']);

        return ['invoice' => $this->toArray($invoice)];
    }

    public function listInvoices(array $filters = []): array
    {
        $result = $this->client()->transactions->list($this->withoutNull([
            'customer_id' => $filters['customer_id'] ?? null,
            'subscription_id' => $filters['subscription_id'] ?? null,
            'status' => $filters['status'] ?? null,
            'per_page' => $filters['limit'] ?? null,
            'after' => $filters['starting_after'] ?? null,
        ]));

        $array = $this->toArray($result);

        return [
            'data' => $array['data'] ?? [],
            'has_more' => (bool) ($array['meta']['has_more'] ?? false),
            'next_cursor' => $array['meta']['next'] ?? null,
        ];
    }

    private function client(): object
    {
        if ($this->client !== null) {
            return $this->client;
        }

        if (!class_exists('\Paddle\\SDK\\Client')) {
            throw new ProviderException('paddlehq/paddle-php-sdk is not installed. Run composer install.');
        }

        $apiKey = (string) ($this->config['api_key'] ?? '');
        if ($apiKey === '') {
            throw new ProviderException('Paddle provider requires config key "api_key".');
        }

        $this->client = new \Paddle\SDK\Client($apiKey);

        return $this->client;
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

        if (is_object($value) && method_exists($value, 'jsonSerialize')) {
            $serialized = $value->jsonSerialize();
            if (is_array($serialized)) {
                return $serialized;
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
