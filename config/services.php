<?php

$secret = \Config::get('mailgun::config.api_key');

return [
    'mailgun' => [
        'domain' => 'razorpay.com',
        'secret' => $secret,
    ]
];
