<?php
return [
    'testTerminalOnboardCallback' => [
        'request' => [
            'url'       => '/terminals/onboard/wallet_paypal/callback/test',
            'method'    => 'POST',
            'content'   => [
                'foo'   => 'bar',
            ],
        ],
        'response' => [
            'content' => [
                'success' => true
            ],
        ],
    ],

    'testTerminalOnboardCallbackTerminalsServiceError' => [
        'request' => [
            'url'       => '/terminals/onboard/wallet_paypal/callback/test',
            'method'    => 'POST',
        ],
        'response' => [
            'content' => [
                'success' => false
            ],
        ],
    ],

];
