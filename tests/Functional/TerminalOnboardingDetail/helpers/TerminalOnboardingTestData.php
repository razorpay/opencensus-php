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

    'testTerminalOnboardCallbackAuthorizationErrorWithoutHeaders' => [
        'request' => [
            'url'       => '/terminals/onboard/wallet_paypal/callback/test',
            'method'    => 'POST',
            'content'   => [
                'foo'   => 'bar',
            ]
        ],
        'response' => [
            'content'     => [
                'error' => [
                    'code'        => 'BAD_REQUEST_ERROR',
                    'description' => 'Authentication failed',
                ],
            ],
            'status_code' => 400,
        ],
    ],

    'testTerminalOnboardCallbackAuthorizationErrorWithHeaders' => [
        'request' => [
            'url'       => '/terminals/onboard/wallet_paypal/callback/test',
            'method'    => 'POST',
            'server' => [
                'HTTP_paypal-auth-algo'          =>'SHA256withRSA',
                'HTTP_paypal-cert-url'           =>'https://api.sandbox.paypal.com/v1/notifications/certs/CERT-360caa42-fca2a594-5a29e601',
                'HTTP_paypal-transmission-sig'   =>'123',
                'HTTP_paypal-transmission-time'  =>'2023-01-12T19:32:58Z',
                'HTTP_paypal-transmission-id'    => 'eb9b29a0-92af-11ed-80ef-0d7c791d2660',
            ],
            'content'   => [
                'foo'   => 'bar',
            ]
        ],
        'response' => [
            'content'     => [
                'error' => [
                    'code'        => 'BAD_REQUEST_ERROR',
                    'description' => 'Authentication failed',
                ],
            ],
            'status_code' => 400,
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
