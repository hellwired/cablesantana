<?php
namespace CableColor;

require_once __DIR__ . '/../vendor/autoload.php';

use Stripe\Stripe;
use Stripe\Checkout\Session;
use Stripe\Exception\ApiErrorException;

class StripePaymentGateway implements PaymentGatewayInterface {
    public function __construct() {
        Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']);
    }

    public function createCheckoutSession($amount, $currency, $description, $successUrl, $cancelUrl, $metadata = []) {
        try {
            $session = Session::create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => $currency,
                        'product_data' => [
                            'name' => $description,
                        ],
                        'unit_amount' => (int)($amount * 100), // Stripe expects cents
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'success_url' => $successUrl . '&session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => $cancelUrl,
                'metadata' => $metadata,
            ]);

            return [
                'id' => $session->id,
                'url' => $session->url
            ];
        } catch (ApiErrorException $e) {
            error_log("Stripe Error: " . $e->getMessage());
            return null;
        }
    }

    public function verifyPayment($sessionId) {
        try {
            $session = Session::retrieve($sessionId);
            return $session->payment_status === 'paid';
        } catch (ApiErrorException $e) {
            error_log("Stripe Verify Error: " . $e->getMessage());
            return false;
        }
    }
}
