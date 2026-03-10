<?php
namespace CableColor;

interface PaymentGatewayInterface {
    /**
     * Create a payment session or intent.
     *
     * @param float $amount Amount to charge.
     * @param string $currency Currency code (e.g., 'usd').
     * @param string $description Description of the charge.
     * @param string $successUrl URL to redirect on success.
     * @param string $cancelUrl URL to redirect on cancel.
     * @param array $metadata Additional metadata.
     * @return array Session data (id, url).
     */
    public function createCheckoutSession($amount, $currency, $description, $successUrl, $cancelUrl, $metadata = []);

    /**
     * Verify a payment session.
     *
     * @param string $sessionId The session ID to verify.
     * @return bool True if paid, false otherwise.
     */
    public function verifyPayment($sessionId);
}
