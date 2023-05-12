<?php

return [
    'testFetchInternationalVirtualAccounts' => [
        'request'  => [
            'url'       => '/international/virtual_accounts',
            'method'    => 'GET',
        ],
        'response' => [
            'content' => [

            ]
        ],
    ],

    'testFetchInternationalVirtualAccountsByValidVACurrency' => [
        'request'  => [
            'url'       => '/international/virtual_account/USD',
            'method'    => 'GET',
        ],
        'response' => [
            'content' => [

            ]
        ]
    ],

    'testFetchInternationalVirtualAccountsByVACurrencyAmountAndCurrency' => [
        'request'  => [
            'url'       => '/international/virtual_account/{va_currency}?&amount=%s&currency=%s',
            'method'    => 'GET',
        ],
        'response' => [
            'content' => [

            ]
        ]
    ],

    'testFetchInternationalVirtualAccountsByVACurrencyNotSupported' => [
        'request'  => [
            'url'       => '/international/virtual_account/{va_currency}',
            'method'    => 'GET',
        ],
        'response' => [
            'content' => [

            ]
        ]
    ],

    'testFetchIntlVAWithPreferredRoutingCodeConfigNotPresentSWIFT' => [
        'request'  => [
            'url'       => '/international/virtual_accounts',
            'method'    => 'GET',
        ],
        'response' => [
            'content' => [

            ]
        ],
    ],

    'testFetchIntlVAWithPreferredRoutingCodeConfigPresent' => [
        'request'  => [
            'url'       => '/international/virtual_accounts',
            'method'    => 'GET',
        ],
        'response' => [
            'content' => [

            ]
        ],
    ],
];
