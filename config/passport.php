<?php
// Configurations for razorpay/edge-passport-php.
return [
    // Public key for upstream services to use to verify passport jwt.
    // Newer versions of lib would not have required str_replace.
    // Ref: https://github.com/vlucas/phpdotenv/issues/261.
    'public_key' => str_replace('\n', PHP_EOL, env('PASSPORT_PUBLIC_KEY')),
];
