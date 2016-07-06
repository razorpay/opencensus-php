<?php

$secret = config('mailgun.api_key');

return [
    'mailgun' => [
        'domain' => 'razorpay.com',
        'secret' => $secret,
    ]
];
