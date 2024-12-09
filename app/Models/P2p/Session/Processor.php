<?php

namespace RZP\Models\P2p\Session;

use RZP\Models\P2p\Base;
/**
 * @property Validator $validator
 * Class Processor
 */
class Processor extends Base\Processor
{
    /**
     * This is a function to get preferences for a razorpay SDK to choose client side bank sdk
     * This will be generalized more later with multi bank setup
     * @param array $input
     *
     * @return array
     */
    public function create(array $input): array
    {

        $this->initialize(Action::CREATE_SESSION, $input,true);

        $currentTimeInSeconds = time();
        $expireAt = $currentTimeInSeconds + 600;

        // TODO: Use passport util to initialize the passport structure
        $passportPayload = [
            'consumer' => [
                'id' => $input[Entity::CUSTOMER_REFERENCE],
                'type' => 'customer',
                'meta' => [
                    Entity::MERCHANT_ID => $this->context()->getMerchant()->getId(),
                ],
            ],
            'identified' => true,
            'authenticated' => true,
            'iss' => $currentTimeInSeconds,
            'exp' => $expireAt,
        ];

        $encryption_key  = $this->app['config']['app']['p2p']['encryption_key'];
        $token = $this->generateSessionToken(
            json_encode($passportPayload),
            base64_decode($encryption_key),
        );

        return [
            Entity::TOKEN => $token,
            Entity::EXPIRE_AT => $expireAt,
        ];

    }

    private function generateSessionToken($payload, $encryption_key): string
    {
        // Define nonce length (24 bytes for NaCl secret-box
        $nonce_length = SODIUM_CRYPTO_SECRETBOX_NONCEBYTES;

        // Generate a 24-byte nonce
        $nonce = random_bytes($nonce_length);

        // Encrypt the payload using the nonce and the encryption key
        $encrypted_msg = sodium_crypto_secretbox($payload, $nonce, $encryption_key);

        // Combine nonce and encrypted message
        $combined = $nonce . $encrypted_msg;

        // Base64 encode the result.
        $session_token = base64_encode($combined);
        // Clear sensitive data from memory
        sodium_memzero($encryption_key);
        sodium_memzero($nonce);
        return $session_token;
    }
}
