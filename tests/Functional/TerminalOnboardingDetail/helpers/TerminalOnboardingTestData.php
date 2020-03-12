<?php
return [
    'testTerminalOnboardCallback' => [
        'request' => [
            'url'       => '/terminals/onboard/paypal/callback/test',
            'method'    => 'POST',
        ],
        'response' => [
            'content' => [
                'success' => true
            ],
        ],
    ]
];
