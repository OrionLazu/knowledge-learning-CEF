<?php

namespace App\Service;

use App\Entity\Purchase;
use Stripe\Checkout\Session;
use Stripe\StripeClient;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Payment component built on Stripe Checkout, used in sandbox (test) mode.
 *
 * The platform creates a Checkout session describing the order, redirects the client to the
 * payment page hosted by Stripe, then asks Stripe whether the session was paid. No card data
 * ever goes through the application. Test payments use Stripe test cards (4242 4242 4242 4242).
 */
class StripeService
{
    public const CURRENCY = 'eur';

    private ?StripeClient $client;

    /**
     * @param string            $secretKey Stripe secret key, read from the STRIPE_SECRET_KEY variable
     * @param StripeClient|null $client    client injected by the tests; created on demand otherwise
     */
    public function __construct(
        #[Autowire('%env(STRIPE_SECRET_KEY)%')]
        private readonly string $secretKey,
        ?StripeClient $client = null,
    ) {
        $this->client = $client;
    }

    /**
     * Tells whether a secret key is configured.
     */
    public function isConfigured(): bool
    {
        return '' !== trim($this->secretKey);
    }

    /**
     * Tells whether the key belongs to the Stripe test mode (sandbox).
     */
    public function isTestMode(): bool
    {
        return str_starts_with($this->secretKey, 'sk_test_') || str_starts_with($this->secretKey, 'rk_test_');
    }

    /**
     * Creates the Stripe Checkout session matching an order.
     *
     * @param string $successUrl return URL after payment; Stripe replaces "{CHECKOUT_SESSION_ID}" in it
     * @param string $cancelUrl  return URL if the client gives up the payment
     *
     * @throws \LogicException                     if the order is empty or Stripe is not configured
     * @throws \Stripe\Exception\ApiErrorException if Stripe returns an error
     */
    public function createCheckoutSession(Purchase $purchase, string $successUrl, string $cancelUrl): Session
    {
        if ($purchase->getItems()->isEmpty()) {
            throw new \LogicException('An empty order cannot be paid.');
        }

        $lineItems = [];
        foreach ($purchase->getItems() as $item) {
            $lineItems[] = [
                'quantity' => 1,
                'price_data' => [
                    'currency' => self::CURRENCY,
                    'unit_amount' => $item->getUnitPrice(),
                    'product_data' => ['name' => $item->getLabel()],
                ],
            ];
        }

        return $this->getClient()->checkout->sessions->create([
            'mode' => 'payment',
            'line_items' => $lineItems,
            'customer_email' => $purchase->getCustomer()->getEmail(),
            'client_reference_id' => (string) $purchase->getId(),
            'metadata' => ['purchase_id' => (string) $purchase->getId()],
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
        ]);
    }

    /**
     * Asks Stripe whether the session was paid.
     *
     * The answer comes from the Stripe API and not from the return URL, which could be forged.
     *
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function isCheckoutSessionPaid(string $sessionId): bool
    {
        return 'paid' === $this->getClient()->checkout->sessions->retrieve($sessionId)->payment_status;
    }

    /**
     * Returns the Stripe client, refusing any key that is not a test key.
     */
    private function getClient(): StripeClient
    {
        if (!$this->isConfigured()) {
            throw new \LogicException('Stripe is not configured: set STRIPE_SECRET_KEY in .env.local.');
        }
        // Safety net: this platform is a prototype, a live key must never be able to charge a card.
        if (!$this->isTestMode()) {
            throw new \LogicException('Only a Stripe test key (sk_test_...) is accepted.');
        }

        return $this->client ??= new StripeClient($this->secretKey);
    }
}
