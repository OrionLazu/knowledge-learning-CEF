<?php

namespace App\Tests\Support;

use Stripe\HttpClient\ClientInterface;
use Stripe\Util\CaseInsensitiveArray;

/**
 * Fake HTTP client plugged into the Stripe library instead of cURL.
 *
 * It records the requests and returns JSON answers prepared by the test: the real code of
 * stripe-php runs, without any network call.
 */
final class FakeStripeHttpClient implements ClientInterface
{
    /** @var list<array{method: string, url: string, params: array<string, mixed>}> */
    public array $requests = [];

    /** @var list<array{int, array<string, mixed>}> */
    private array $responses = [];

    /**
     * Prepares the next answer returned by "Stripe".
     *
     * @param array<string, mixed> $body
     */
    public function queueResponse(array $body, int $status = 200): void
    {
        $this->responses[] = [$status, $body];
    }

    /**
     * Prepares the answer to the creation of a Checkout session.
     */
    public function queueCheckoutSession(string $id, string $paymentStatus = 'unpaid'): void
    {
        $this->queueResponse([
            'id' => $id,
            'object' => 'checkout.session',
            'url' => 'https://checkout.stripe.com/c/pay/'.$id,
            'payment_status' => $paymentStatus,
        ]);
    }

    public function request($method, $absUrl, $headers, $params, $hasFile, $apiMode = 'v1', $maxNetworkRetries = null)
    {
        $this->requests[] = ['method' => strtoupper($method), 'url' => $absUrl, 'params' => (array) $params];

        if ([] === $this->responses) {
            throw new \LogicException('No Stripe answer prepared for '.$method.' '.$absUrl);
        }

        [$status, $body] = array_shift($this->responses);

        return [json_encode($body, JSON_THROW_ON_ERROR), $status, new CaseInsensitiveArray(['request-id' => 'req_test'])];
    }
}
