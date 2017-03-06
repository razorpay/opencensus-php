<?php

use RZP\Error\ErrorCode;
use RZP\Exception;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testCurrencyRatesLatest' => [
        'request' => [
            'content' => [
            ],
            'method' => 'POST',
            'url' => '/currency/USD/rates',
        ],
        'response' => [
            'status_code' => 200,
            'content' => [
                'INR' => 10,
                'USD' => 1
            ],
        ],
    ],

    'testGetCurrencyRates' => [
        'request' => [
            'content' => [
            ],
            'method' => 'GET',
            'url' => '/currency/USD/rates',
        ],
        'response' => [
            'content' => [
                'INR' => 10,
                'USD' => 1
            ]
        ],
    ],
];
