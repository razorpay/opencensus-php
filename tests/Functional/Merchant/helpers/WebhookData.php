<?php

use RZP\Gateway\Hdfc;
use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

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

    'testCreateWebhookWithLargerSecret' => [
        'request' => [
            'url' => '/webhooks',
            'content' => [
                'url' => 'http://example.com',
                'secret' => 'cef6950d4648d0257f8ea6f1198b23f2bb892d1ccef6950d4648d0257f8ea6f1198b23f2bb892d1ccef6950d4648d0257f8ea6f1198b23f2bb892d1ccef6950d4648d0257f8ea6f1198b23f2bb892d1ccef6950d4648d0257f8ea6f1198b23f2bb892d1ccef6950d4648d0257f8ea6f1198b23f2bb892d1ccef6950d4648d0257f8ea6f1198b23f2bb892d1ccef6950d4648d0257f8ea6f1198b23f2bb892d1ccef6950d4648d0257f8ea6f1198b23f2bb892d1ccef6950d4648d0257f8ea6f1198b23f2bb892d1c',
                'events' => [
                    'payment.authorized' => '1',
                ],
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The secret may not be greater than 255 characters.'
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],
    'testCreateWebhookWithDisallowedPort' => [
        'request' => [
            'url' => '/webhooks',
            'content' => [
                'url' => 'http://example.com:90',
                'events' => [
                    'payment.authorized' => '1',
                ],
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Only 80 or 443 port is currently allowed in webhook url.'
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testGetWebhooks' => [
        'request' => [
            'url' => '/webhooks',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                   'entity' => 'collection',
                    'count' => 1,
                    'items' => [
                        [
                            'url' => 'http://localhost/v1/dummy/route',
                            'events' => [
                                'payment.authorized' => true
                            ],
                            'active' => true
                        ]
                    ]
            ]
        ]
    ],

    'testRecreateWebhook' => [
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
            'class' => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
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
            'class' => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testWebhookEventData' => [
        'event' => [
            'event' => 'payment.authorized',
            'contains' => ['payment'],
            'payload' => [
                'payment' => [
                    'entity' => [
                        // 'id' => 'pay_4WVwsa1ZAIsNZ5',
                        'entity' => 'payment',
                        'amount' => 50000,
                        'currency' => 'INR',
                        'status' => 'authorized',
                        'amount_refunded' => 0,
                        'refund_status' => null,
                        'captured' => false,
                        'description' => 'random description',
                        'email' => 'a@b.com',
                        'contact' => '+919918899029',
                        'notes' => ['merchant_order_id' => 'random order id'],
                        'error_code' => null,
                        'error_description' => null,
                        // 'created_at' => 1449782144,
                    ],
                ],
            ],
            // 'created_at' => 1449782144,
        ],
        // 'webhook_id' => '4WVwsVEmeO3wwp',
    ],

    'testOrderPaidWebhookEventData' => [
        'event' => [
            'event' => 'order.paid',
            'contains' => ['order'],
            'payload' => [
                'order' => [
                    'entity' => [
                        'entity' => 'order',
                        'amount' => 50000,
                        'receipt' => 'random',
                        'currency' => 'INR',
                        'status' => 'paid',
                        'attempts' => 1,
                        'notes' => []
                    ],
                ],
            ],
        ],
    ],

    'testWebhookEventDataJustBeforeFiring' => [
        'url' => 'http://localhost/v1/dummy/route',
        'method' => 'post',
        'content' => [
            'entity' => 'event',
            'event' => 'payment.authorized',
            'contains' => ['payment'],
            'payload' => [
                'payment' => [
                    'entity' => [
//                        'id' => 'pay_4WVwsa1ZAIsNZ5',
                        'entity' => 'payment',
                        'method' => 'card',
                        'amount' => 50000,
                        'currency' => 'INR',
                        'status' => 'authorized',
                        'amount_refunded' => 0,
                        'refund_status' => null,
                        'captured' => false,
                        'description' => 'random description',
                        'email' => 'a@b.com',
                        'contact' => '+919918899029',
                        'notes' => ['merchant_order_id' => 'random order id'],
                        'error_code' => null,
                        'error_description' => null,
                        // 'created_at' => 1449782144,
                    ],
                ],
            ],
            // 'created_at' => 1449782144,
        ],
        // 'webhook_id' => '4WVwsVEmeO3wwp',
    ],
    'testSecretValueInWebhookEventDataJustBeforeFiring' => [
        'url' => 'http://localhost/v1/dummy/route',
        'method' => 'post',
        'content' => [
            'entity' => 'event',
            'event' => 'payment.authorized',
            'contains' => ['payment'],
            'payload' => [
                'payment' => [
                    'entity' => [
//                        'id' => 'pay_4WVwsa1ZAIsNZ5',
                        'entity' => 'payment',
                        'method' => 'card',
                        'amount' => 50000,
                        'currency' => 'INR',
                        'status' => 'authorized',
                        'amount_refunded' => 0,
                        'refund_status' => null,
                        'captured' => false,
                        'description' => 'random description',
                        'email' => 'a@b.com',
                        'contact' => '+919918899029',
                        'notes' => ['merchant_order_id' => 'random order id'],
                        'error_code' => null,
                        'error_description' => null,
                        // 'created_at' => 1449782144,
                    ],
                ],
            ],
            // 'created_at' => 1449782144,
        ],
        // 'webhook_id' => '4WVwsVEmeO3wwp',
    ],
];
