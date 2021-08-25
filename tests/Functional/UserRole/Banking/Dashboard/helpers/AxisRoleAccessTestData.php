<?php


use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;

return [

    'testFlowWhenAxisUserIsPresent' => [
        'request'  => [
            'url'    => '/contacts/cont_1000000contact',
            'method' => 'GET',
            'server' => [
                'HTTP_X-Request-Origin' => 'https://x.razorpay.com'
            ],
        ],
        'response' => [
            'content' => [
                'id'     => 'cont_1000000contact',
                'entity' => 'contact',
            ],
        ],
    ],

    'testFlowWhenBankingUserIsPresent' => [
        'request'  => [
            'url'    => '/contacts/cont_1000000contact',
            'method' => 'GET',
            'server' => [
                'HTTP_X-Request-Origin' => 'https://x.razorpay.com'
            ],
        ],
        'response' => [
            'content' => [
                'id'     => 'cont_1000000contact',
                'entity' => 'contact',
            ],
        ],
    ],
];
