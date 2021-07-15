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

    'testProxyAuthForGetOptimizerGateways' => [
        'request' => [
            'url'       => '/terminals/proxy/optimizer/supported_gateways',
            'method'    => 'GET',
        ],
        'response' => [
            'content' => [
                'payu' => [
                    'Key' => [
                        'data_type'  => 'string',
                        'data_value' => 'payu key',
                        'min_length' => 6,
                    ],
                    'Salt' => [
                        'data_type'  => 'string',
                        'data_value' => 'payu salt',
                        'min_length' => 8,
                    ],
                ],
            ],
        ],
    ],

    'testProxyAuthForAddingOptimizerProvider' => [
        'request' => [
            'url'       => '/terminals/proxy/optimizer/mid/provider',
            'method'    => 'POST',
        ],
        'response' => [
            'content' => [
                'terminal' => [
                    'id' => 'HWo8Z0G0c0az74',
                ],
            ],
        ],
    ],

    'testProxyAuthForEditingOptimizerProvider' => [
        'request' => [
            'url'       => '/terminals/proxy/optimizer/mid/provider',
            'method'    => 'PUT',
        ],
        'response' => [
            'content' => [
                'terminal' => [
                    'id' => 'HWo8Z0G0c0az74',
                ],
            ],
        ],
    ],

    'testProxyAuthForListOptimizerMerchantProviders' => [
        'request' => [
            'url'       => '/terminals/proxy/optimizer/list/mid/provider',
            'method'    => 'GET',
        ],
        'response' => [
            'content' => [
                [
                    'Provider_name' => 'PayU',
                    'Description'   => 'Cards and UPI',
                    'Gateway'       => 'payu',
                ],
            ],
        ],
    ],
];
