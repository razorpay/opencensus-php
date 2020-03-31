<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [

    'testInitiateOnboarding' => [
        'request' => [
            'method' => 'POST',
            'url' => '/terminals/onboard',
            'content' => ['gateway' => 'wallet_paypal']
        ],
        'response' => [
            'content' => [
            ],
            'status_code'   => 200,
        ],
    ],

    'testInitiateOnboardingWithNoGatewayInInput' => [
        'request' => [
            'method' => 'POST',
            'url' => '/terminals/onboard',
            'content' => []
        ],
        'response' => [
            'content' => [
            ],
            'status_code'   => 200,
        ],
    ],

];
