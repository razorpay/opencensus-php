<?php

$supportedCurrencyData = require(__DIR__ . '/SupportedCurrencyResponse.php');

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

    'testGetPaymentCurrencies' => [
        'request' => [
            'content' => [
            ],
            'method' => 'GET',
            'url' => '/currency/all',
        ],
        'response' => [
            'content' => $supportedCurrencyData
        ],
    ],

    'testGetPaymentCurrenciesProxy' => [
        'request' => [
            'content' => [
            ],
            'method' => 'GET',
            'url' => '/currency/all/proxy',
        ],
        'response' => [
            'content' => $supportedCurrencyData
        ],
    ],

    'testGetPaymentCurrenciesAdminProxy' => [
        'request' => [
            'content' => [
            ],
            'method' => 'GET',
            'url' => '/currency/all/proxy',
        ],
        'response' => [
            'content' => $supportedCurrencyData
        ],
    ],
];
