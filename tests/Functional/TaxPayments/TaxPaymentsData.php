<?php

return [
    'testSettingsInternalApiAddOrUpdate'                  => [
        'request'  => [
            'method'  => 'POST',
            'server'  => [
                'HTTP_X-Razorpay-Account' => '10000000000000',
            ],
            'url'     => '/settings_internal/tax_payments',
            'content' => [
                'test_key' => 'test_value'
            ],
        ],
        'response' => [
            'content' => [
                'success' => true
            ]
        ]
    ],
    'testSettingsInternalApiGet'                          => [
        'request'  => [
            'method'  => 'GET',
            'server'  => [
                'HTTP_X-Razorpay-Account' => '10000000000000',
            ],
            'url'     => '/settings_internal/tax_payments',
            'content' => []
        ],
        'response' => [
            'content' => [
                'settings' => [
                    'test_key' => 'test_value'
                ]
            ],
        ]
    ],
    'testTaxPaymentSettingGetCallsServiceMethods'         => [
        'request'  => [
            'method' => 'GET',
            'url'    => '/tax-payments/settings',
        ],
        'response' => [
            'content' => []
        ]
    ],
    'testTaxPaymentSettingAddOrUpdateCallsServiceMethods' => [
        'request'  => [
            'method' => 'POST',
            'url'    => '/tax-payments/settings',
        ],
        'response' => [
            'content' => []
        ]
    ],
    'testGetTaxPaymentCallsServiceMethod'                 => [
        'request'  => [
            'method' => 'GET',
            'url'    => '/tax-payments/txpy_1234',
        ],
        'response' => [
            'content' => []
        ]
    ],
    'testListTaxPaymentCallsServiceMethod'                => [
        'request'  => [
            'method' => 'GET',
            'url'    => '/tax-payments',
        ],
        'response' => [
            'content' => []
        ]
    ]
];
