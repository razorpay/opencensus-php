<?php

use EE\Error\ErrorCode;
use EE\Error\PublicErrorCode;
use EE\Error\PublicErrorDescription;
use Gateway\Hdfc;

return [
    'testCreateWebhook' => [
        'request' => [
            'url' => '/webhooks',
            'content' => [
                'url' => 'http://example.com',
                'events' => [
                    'payment.authorized' => '1',
                ],
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'merchant_id' => '10000000000000',
                'url' => 'http://example.com',
                'events' => [
                    'payment.authorized' => true,
                ],
                'active' => true,
                'failure_count' => 0,
            ]
        ]
    ],

    'testGetWebhooks' => [
        'request' => [
            'url' => '/webhooks',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                   'entity' => "collection",
                    'count' => 1,
                    'items' => [
                        [
                            'url' => "http://random.com",
                            'events' => [
                                'payment.authorized' => true
                            ],
                            'active' => true
                        ]
                    ]
            ]
        ]
    ],

    'testEditWebhook' => [
        'request' => [
            'content' => [
                'url' => 'http://random2.com',
                'events' => [
                    'payment.authorized' => '0',
                ],
                'active' => '0',
            ],
            'method' => 'put',
        ],
        'response' => [
            'content' => [
                'merchant_id' => '10000000000000',
                'url' => 'http://random2.com',
                'events' => [
                    'payment.authorized' => false,
                ],
                'active' => false,
                'failure_count' => 0,
            ],
        ]
    ],

    'testCreateWebhookWrongUrl' => [
        'request' => [
            'url' => '/webhooks',
            'content' => [
                'url' => 'random2com',
                'events' => [
                    'payment.authorized' => '0',
                ],
            ],
            'method' => 'post',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => EE\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],
];
