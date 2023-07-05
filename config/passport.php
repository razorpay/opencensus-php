<?php
// Configurations for razorpay/edge-passport-php.
return [
    // Public key for upstream services to use to verify passport jwt.
    // Newer versions of lib would not have required str_replace.
    // Ref: https://github.com/vlucas/phpdotenv/issues/261.
    'public_key' => str_replace('\n', PHP_EOL, env('PASSPORT_PUBLIC_KEY')),

    // Configurations specific to API follows which too is a passport jwt issuer.
    'issuer_id'                   => 'api',                                                          // The iss header in jwt.
    'issuer_private_key'          => str_replace('\n', PHP_EOL, env('PASSPORT_ISSUER_PRIVATE_KEY')), // The private key for signing jwt.
    'issuer_private_key_id'       => 'apiv1',                                                        // The kid header for jwt.
    'issuer_passport_expire_secs' => 300,
    'jwks_host' => env("PASSPORT_JWKS_HOST"),
];
