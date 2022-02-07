<?php

return [
    'testOAuthAppAuthorizedMail' => [
        'request' => [
            'url' => '/oauth/notify/app_authorized',
            'method' => 'POST',
            'content' => []
        ],
        'response' => [
            'content' => [
            ],
            'status_code' => 200,
        ],
    ],

    'testOAuthSkipNotification' => [
        'request' => [
            'url' => '/oauth/notify/app_authorized',
            'method' => 'POST',
            'content' => []
        ],
        'response' => [
            'content' => [
            ],
            'status_code' => 200,
        ],
    ],

    'testOAuthCompetitorAppAuthorizedMail' => [
        'request' => [
            'url' => '/oauth/notify/app_authorized',
            'method' => 'POST',
            'content' => []
        ],
        'response' => [
            'content' => [
            ],
            'status_code' => 200,
        ],
    ],

    'testOAuthTallyOTPMail' => [
        'request' => [
            'url' => '/oauth/notify/tally_auth_otp',
            'method' => 'POST',
            'content' => []
        ],
        'response' => [
            'content' => [
            ],
            'status_code' => 200,
        ],
    ],

    'testOAuthBlockResendingCompetitorAppAuthorizedMail' => [
        'request' => [
            'url' => '/oauth/notify/app_authorized',
            'method' => 'POST',
            'content' => []
        ],
        'response' => [
            'content' => [
            ],
            'status_code' => 200,
        ],
    ],
];
