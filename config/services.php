<?php

return [
    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN', 'razorpay.com'),
        'secret' => env('MAILGUN_API_KEY'),
    ]
];
