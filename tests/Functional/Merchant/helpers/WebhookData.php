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
                'url' => 'http://webhook.com',
                'events' => [
                    'payment.authorized' => '1',
                ],
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'url' => 'http://webhook.com',
                'events' => [
                    'payment.authorized' => true,
                ],
                'active' => true,
            ]
        ]
    ],

    'testEditDisableWebhookOnPrivateAuth' => [
        'request' => [
            'url' => '/webhooks',
            'content' => [
                'url' => 'http://webhook.com',
                'events' => [
                    'payment.authorized' => '1',
                ],
                'disable_on_failure' => '0',
            ],
            'method' => 'PUT'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'disable on failure is/are not required and should not be sent',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testEditDisableWebhookOnAdminProxyAuth' => [
        'request' => [
            'url' => '/webhooks',
            'content' => [
                'url' => 'http://webhook.com',
                'events' => [
                    'payment.authorized' => '1',
                ],
                'disable_on_failure' => '0',
            ],
            'method' => 'PUT'
        ],
        'response' => [
            'content' => [
                'url' => 'http://webhook.com',
                'events' => [
                    'payment.authorized' => true,
                ],
                'active' => true,
            ]
        ]
    ],

    'testEditWebhookForProductBankingWithInvalidEvents' => [
        'request' => [
            'url'       => '/webhooks/10000000000000',
            'server' => [
                'HTTP_X-Request-Origin' => 'https://x.razorpay.com',
            ],
            'content'   => [
                'url'       => 'http://webhook.com',
                'events'    => [
                    'payment.authorized' => '1',
                ],
            ],
            'method'    => 'PUT',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Invalid event name/names: payment.authorized'
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateWebhookWhenAlreadyCreated' => [
        'request' => [
            'url' => '/webhooks',
            'content' => [
                'url' => 'http://webhook.com',
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
                    'description' => 'Webhook already created.'
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateAppWebhook' => [
        'request' => [
            'url' => '/oauth/applications/10000000000App/webhooks',
            'content' => [
                'url' => 'http://webhook.com',
                'events' => [
                    'payment.authorized' => '1',
                ],
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'url'            => 'http://webhook.com',
                'events'         => [
                    'payment.authorized' => true,
                ],
                'active'         => true,
                'application_id' => '10000000000App'
            ]
        ],
    ],

    'testCreateWebhookWithLargerSecret' => [
        'request' => [
            'url' => '/webhooks',
            'content' => [
                'url' => 'http://webhook.com',
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

    'testCreateAppWebhookInvalidPartnerType' => [
        'request'   => [
            'url'     => '/oauth/applications/10000000000Appp/webhooks',
            'content' => [
                'url'    => 'http://webhook.com',
                'events' => [
                    'payment.authorized' => '1',
                ],
            ],
            'method'  => 'POST'
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_INVALID_PARTNER_ACTION,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_PARTNER_ACTION,
        ],
    ],

    'testCreateAppWebhookPurePlatform' => [
        'request' => [
            'url' => '/oauth/applications/10000000000App/webhooks',
            'content' => [
                'url' => 'http://webhook.com',
                'events' => [
                    'payment.authorized' => '1',
                ],
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'url'            => 'http://webhook.com',
                'events'         => [
                    'payment.authorized' => true,
                ],
                'active'         => true,
                'application_id' => '10000000000App'
            ]
        ],
    ],

    'testCreateAppWebhookOAuthTag' => [
        'request' => [
            'url' => '/oauth/applications/10000000000App/webhooks',
            'content' => [
                'url' => 'http://webhook.com',
                'events' => [
                    'payment.authorized' => '1',
                ],
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'url'            => 'http://webhook.com',
                'events'         => [
                    'payment.authorized' => true,
                ],
                'active'         => true,
                'application_id' => '10000000000App'
            ]
        ],
    ],

    'testCreateAppWebhookBankWithOAuthTag' => [
        'request' => [
            'url' => '/oauth/applications/10000000000App/webhooks',
            'content' => [
                'url' => 'http://webhook.com',
                'events' => [
                    'payment.authorized' => '1',
                ],
            ],
            'method' => 'POST'
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_INVALID_PARTNER_ACTION,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_PARTNER_ACTION,
        ],
    ],

    'testCreateAppWebhookFullyManagedWithOAuthTag' => [
        'request' => [
            'url' => '/oauth/applications/10000000000App/webhooks',
            'content' => [
                'url' => 'http://webhook.com',
                'events' => [
                    'payment.authorized' => '1',
                ],
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'url'            => 'http://webhook.com',
                'events'         => [
                    'payment.authorized' => true,
                ],
                'active'         => true,
                'application_id' => '10000000000App'
            ]
        ],
    ],

    'testCreateWebhookForProductBanking' => [
        'request' => [
            'url'       => '/webhooks',
            'server' => [
                'HTTP_X-Request-Origin' => 'https://x.razorpay.com',
            ],
            'content'   => [
                'url'       => 'http://webhook.com',
                'events'    => [
                    'transaction.created' => '1',
                ],
            ],
            'method'    => 'POST',
        ],
        'response' => [
            'content' => [
                'url'       => 'http://webhook.com',
                'events'    => [
                    'transaction.created'   => true,
                    'payout.created'        => false,
                    'payout.processed'      => false,
                    'payout.reversed'       => false,
                ],
                'active'    => true,
            ],
        ],
    ],

    'testCreateWebhookForProductBankingWithInvalidEvents' => [
        'request' => [
            'url'       => '/webhooks',
            'server' => [
                'HTTP_X-Request-Origin' => 'https://x.razorpay.com',
            ],
            'content'   => [
                'url'       => 'http://webhook.com',
                'events'    => [
                    'payment.authorized' => '1',
                ],
            ],
            'method'    => 'POST',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Invalid event name/names: payment.authorized'
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
                'url' => 'http://example.com:6000',
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
                    'description' => 'The provided port is restricted and cannot be used in a webhook URL.'
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateWebhookWithInternalIp' => [
        'request' => [
            'url' => '/webhooks',
            'content' => [
                'url' => 'http://10.0.0.1.xip.io',
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
                    'description' => 'URL must point to a public IP address: http://10.0.0.1.xip.io'
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateWebhookWithReservedIp' => [
        'request' => [
            'url' => '/webhooks',
            'content' => [
                'url' => 'http://169.254.169.254.xip.io',
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
                    'description' => 'URL must point to a public IP address: http://169.254.169.254.xip.io'
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateWebhookWithoutHost' => [
        'request' => [
            'url' => '/webhooks',
            'content' => [
                // Valid public IP address
                'url' => 'http://1.2.3.4/hello',
                'events' => [
                    'payment.authorized' => '1',
                ],
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'url' => 'http://1.2.3.4/hello',
                'events' => [
                    'payment.authorized' => true,
                ],
                'active' => true,
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
                'entity' => 'collection',
                'count' => 1,
                'items' => [
                    [
                        'url' => 'http://webhook.com/v1/dummy/route',
                        'events' => [
                            'payment.authorized' => true
                        ],
                        'active' => true
                    ]
                ]
            ]
        ]
    ],

    'testGetWebhookWithSecret' => [
        'request' => [
            'method' => 'GET',
        ],
        'response' => [
            'content' => [
                'url'         => 'http://www.testUrl.com',
                'secret'      => 'BestTestSecretEver',
                'events'      => [
                    'payment.authorized' => true,
                ]
            ]
        ]
    ],

    'testGetWebhooksWithSecret' => [
        'request' => [
            'url' => '/webhooks',
            'method' => 'GET',
        ],
        'response' => [
            'content' => [
                [
                    'url'         => 'http://www.testUrl.com',
                    'secret'      => 'BestTestSecretEver',
                    'events'      => [
                        'payment.authorized' => true,
                    ]
                ]
            ]
        ]
    ],

    'testGetWebhookEvents' => [
        'request' => [
            'url'   => '/webhooks/events/all',
            'method' => 'GET',
        ],
        'response' => [
            'content' => [
                'payment.authorized',
                'payment.failed',
                'payment.captured',
                'payment.dispute.created',
                'order.paid',
                'invoice.paid',
                'invoice.partially_paid',
                'invoice.expired',
            ]
        ]
    ],

    'testGetAppWebhooks' => [
        'request' => [
            'url'    => '/webhooks?application_id=10000000000App',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 1,
                'items'  => [
                    [
                        'url'            => 'http://webhook.com/v1/dummy/route',
                        'events'         => [
                            'payment.authorized' => true
                        ],
                        'active'         => true,
                        'application_id' => '10000000000App',
                    ]
                ]
            ]
        ]
    ],

    'testGetWebhookEventsForProductBanking' => [
        'request' => [
            'url'       => '/webhooks/events/all',
            'method'    => 'GET',
            'server'    => [
                'HTTP_X-Request-Origin' => 'https://x.razorpay.com',
            ],
        ],
        'response' => [
            'content' => [
                'transaction.created',
                'payout.created',
                'payout.processed',
                'payout.reversed',
            ],
        ],
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

    'testCreateAppWebhookInvalidAppId' => [
        'request'  => [
            'url'     => '/oauth/applications/10000000000Appp/webhooks',
            'content' => [
                'url'    => 'http://webhook.com',
                'events' => [
                    'payment.authorized' => '1',
                ],
            ],
            'method'  => 'POST'
        ],
        'response' => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The entity id must be 14 characters.'
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testEditWebhook' => [
        'request' => [
            'content' => [
                'url' => 'https://example.com',
                'events' => [
                    'payment.authorized' => '0',
                ],
                'active' => '0',
            ],
            'method' => 'put',
        ],
        'response' => [
            'content' => [
                'url' => 'https://example.com',
                'events' => [
                    'payment.authorized' => false,
                ],
                'active' => false,
            ],
        ]
    ],

    'testEditWebhookByNonOwnerUser' => [
        'request' => [
            'content' => [
                'url' => 'https://example.com',
                'events' => [
                    'payment.authorized' => '0',
                ],
                'active' => '0',
            ],
            'method' => 'put',
        ],
        'response' => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_UNAUTHORIZED
                ],
            ],
            'status_code' => 400,
        ],
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
        'entity' => 'event',
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
    ],

    'testOrderPaidWebhookEventData' => [
        'mode' => 'test',
        'event' => [
            'entity' => 'event',
            'event' => 'order.paid',
            'contains' => ['payment', 'order'],
            'payload' => [
                'order' => [
                    'entity' => [
                        'entity'          => 'order',
                        // 'partial_payment' => false,
                        'amount'          => 50000,
                        'amount_paid'     => 50000,
                        'amount_due'      => 0,
                        'receipt'         => 'random',
                        'currency'        => 'INR',
                        'status'          => 'paid',
                        'attempts'        => 1,
                        'notes'           => []
                    ],
                ],
                'payment' => [
                    'entity' => [
                        'entity'            => 'payment',
                        'amount'            => 50000,
                        'currency'          => 'INR',
                        'status'            => 'captured',
                        'amount_refunded'   => 0,
                        'refund_status'     => null,
                        'captured'          => true,
                        'description'       => 'random description',
                        'email'             => 'a@b.com',
                        'contact'           => '+919918899029',
                        'notes'             => ['merchant_order_id' => 'random order id'],
                        'error_code'        => null,
                        'error_description' => null,
                    ],
                ],
            ],
        ],
    ],

    'testAppWebhookData' => [
        'url'     => 'http://webhook.com/v1/dummy/route',
        'method'  => 'post',
        'content' => [
            'entity'   => 'event',
            'event'    => 'payment.authorized',
            'contains' => ['payment'],
            'payload'  => [
                'payment' => [
                    'entity' => [
                        'entity'            => 'payment',
                        'amount'            => 50000,
                        'currency'          => 'INR',
                        'status'            => 'authorized',
                        'amount_refunded'   => 0,
                        'refund_status'     => null,
                        'captured'          => false,
                        'description'       => 'random description',
                        'email'             => 'a@b.com',
                        'contact'           => '+919918899029',
                        'notes'             => ['merchant_order_id' => 'random order id'],
                        'error_code'        => null,
                        'error_description' => null,
                    ],
                ],
            ],
        ]
    ],

    'testApp2WebhookData' => [
        'url'     => 'http://exampleapp.com/v1/dummy/route',
        'method'  => 'post',
        'content' => [
            'entity'   => 'event',
            'event'    => 'payment.authorized',
            'contains' => ['payment'],
            'payload'  => [
                'payment' => [
                    'entity' => [
                        'entity'            => 'payment',
                        'amount'            => 50000,
                        'currency'          => 'INR',
                        'status'            => 'authorized',
                        'amount_refunded'   => 0,
                        'refund_status'     => null,
                        'captured'          => false,
                        'description'       => 'random description',
                        'email'             => 'a@b.com',
                        'contact'           => '+919918899029',
                        'notes'             => ['merchant_order_id' => 'random order id'],
                        'error_code'        => null,
                        'error_description' => null,
                    ],
                ],
            ],
        ],
    ],

    'testMerchantWebhookData' => [
        'url'     => 'http://webhook.com/v1/dummy/route',
        'method'  => 'post',
        'content' => [
            'entity'   => 'event',
            'event'    => 'payment.authorized',
            'contains' => ['payment'],
            'payload'  => [
                'payment' => [
                    'entity' => [
                        'entity'            => 'payment',
                        'amount'            => 50000,
                        'currency'          => 'INR',
                        'status'            => 'authorized',
                        'amount_refunded'   => 0,
                        'refund_status'     => null,
                        'captured'          => false,
                        'description'       => 'random description',
                        'email'             => 'a@b.com',
                        'contact'           => '+919918899029',
                        'notes'             => ['merchant_order_id' => 'random order id'],
                        'error_code'        => null,
                        'error_description' => null,
                    ],
                ],
            ],
        ],
    ],

    'testInvoicePaidWebhookEventDataWithOrderAndWithoutInvoice' => [
        'mode' => 'test',
        'event' => [
            'entity' => 'event',
            'event' => 'order.paid',
            'contains' => ['payment', 'order'],
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
                'payment' => [
                    'entity' => [
                        'entity' => 'payment',
                        'amount' => 50000,
                        'currency' => 'INR',
                        'status' => 'captured',
                        'amount_refunded' => 0,
                        'refund_status' => null,
                        'captured' => true,
                        'description' => 'random description',
                        'email' => 'a@b.com',
                        'contact' => '+919918899029',
                        'notes' => ['merchant_order_id' => 'random order id'],
                        'error_code' => null,
                        'error_description' => null,
                    ],
                ],
            ],
        ],
    ],

    'testInvoicePaidWebhookEventData' => [
        'mode' => 'test',
        'event' => [
            'entity' => 'event',
            'event' => 'invoice.paid',
            'contains' => ['payment', 'order', 'invoice'],
            'payload' => [
                'order' => [
                    'entity' => [
                        'entity'          => 'order',
                        'id'              => 'order_100000000order',
                        // 'partial_payment' => false,
                        'amount'          => 1000000,
                        'amount_paid'     => 1000000,
                        'amount_due'      => 0,
                        'receipt'         => 'random',
                        'currency'        => 'INR',
                        'status'          => 'paid',
                        'attempts'        => 1,
                        'notes'           => []
                    ],
                ],
                'payment' => [
                    'entity' => [
                        'entity'            => 'payment',
                        'amount'            => 1000000,
                        'currency'          => 'INR',
                        'status'            => 'captured',
                        'amount_refunded'   => 0,
                        'refund_status'     => null,
                        'captured'          => true,
                        'description'       => 'random description',
                        'email'             => 'a@b.com',
                        'contact'           => '+919918899029',
                        'notes'             => ['merchant_order_id' => 'random order id'],
                        'error_code'        => null,
                        'error_description' => null,
                        'invoice_id'        => 'inv_1000000invoice',
                    ],
                ],
                'invoice' => [
                    'entity' => [
                        'entity'           => 'invoice',
                        'id'               => 'inv_1000000invoice',
                        'customer_id'      => 'cust_100000customer',
                        'order_id'         => 'order_100000000order',
                        'status'           => 'paid',
                        'sms_status'       => 'sent',
                        'email_status'     => 'sent',
                        // 'partial_payment'  => false,
                        'amount'           => 1000000,
                        'amount_paid'      => 1000000,
                        'amount_due'       => 0,
                        'customer_details' => [
                            'name'    => 'test',
                            'email'   => 'test@razorpay.com',
                            'contact' => '1234567890',
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testInvoiceWithoutCustomerDetailsPaidWebhookEventData' => [
        'mode' => 'test',
        'event' => [
            'entity'   => 'event',
            'event'    => 'invoice.paid',
            'contains' => ['payment', 'order', 'invoice'],
            'payload'  => [
                'order' => [
                    'entity'       => [
                        'entity'   => 'order',
                        'id'       => 'order_100000000order',
                        'amount'   => 1000000,
                        'receipt'  => 'random',
                        'currency' => 'INR',
                        'status'   => 'paid',
                        'attempts' => 1,
                        'notes'    => []
                    ],
                ],
                'payment' => [
                    'entity' => [
                        'entity'            => 'payment',
                        'amount'            => 1000000,
                        'currency'          => 'INR',
                        'status'            => 'captured',
                        'amount_refunded'   => 0,
                        'refund_status'     => null,
                        'captured'          => true,
                        'description'       => 'random description',
                        'email'             => 'a@b.com',
                        'contact'           => '+919918899029',
                        'notes'             => ['merchant_order_id' => 'random order id'],
                        'error_code'        => null,
                        'error_description' => null,
                    ],
                ],
                'invoice' => [
                    'entity' => [
                        'entity'           => 'invoice',
                        'id'               => 'inv_1000000invoice',
                        'customer_id'      => null,
                        'order_id'         => 'order_100000000order',
                        'status'           => 'paid',
                        'sms_status'       => 'sent',
                        'email_status'     => 'sent',
                        'customer_details' => [
                            'customer_name'    => null,
                            'customer_contact' => '+919918899029',
                            'customer_email'   => 'a@b.com'
                        ]
                    ],
                ],
            ],
        ],
    ],

    'testWebhookEventDataJustBeforeFiring' => [
        'url' => 'http://webhook.com/v1/dummy/route',
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

    'testExceptionOnWebhookFire' => [
        'url' => 'http://webhook.com/v1/dummy/route',
        'method' => 'post',
        'content' => [
            'entity' => 'event',
            'event' => 'payment.authorized',
            'contains' => ['payment'],
            'payload' => [
                'payment' => [
                    'entity' => [
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
                    ],
                ],
            ],
        ],
    ],

    'testSecretValueInWebhookEventDataJustBeforeFiring' => [
        'url' => 'http://webhook.com/v1/dummy/route',
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

    'testTransferSettlementWebhook' => [
        'event' => [
            'entity'     => 'event',
            'event'      => 'settlement.processed',
            'contains'   => [
                'settlement'
            ],
            'payload'    => [
                'settlement' => [
                    'entity' => [
                        'entity' => 'settlement',
                        'amount' => 2500
                    ]
                ]
            ],
        ]
    ],

    'testRefundSpeedChangedWebhookEventData' => [
        'mode' => 'test',
        'event' => [
            'entity' => 'event',
            'event' => 'refund.speed_changed',
            'contains' => ['refund'],
            'payload' => [
                'refund' => [
                    'entity' => [
                        'entity'          => 'refund',
                        'amount'          => 3470,
                        'currency'        => 'INR',
                        'notes'           => [],
                        'receipt'         => null,
                        'status'          => 'processed',
                        'speed_requested' => 'optimum',
                        'speed_processed' => 'normal',
                        'acquirer_data'   => [
                            'arn' => null,
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testRefundFailedWebhookEventData' => [
        'mode' => 'test',
        'event' => [
            'entity' => 'event',
            'event' => 'refund.failed',
            'contains' => ['refund'],
            'payload' => [
                'refund' => [
                    'entity' => [
                        'entity'        => 'refund',
                        'amount'        => 3459,
                        'currency'      => 'INR',
                        'notes'         => [],
                        'receipt'       => null,
                        'status'        => 'failed',
                        'acquirer_data' => [
                            'arn' => null,
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testRefundProcessedInstantWebhookEventData' => [
        'mode' => 'test',
        'event' => [
            'entity' => 'event',
            'event' => 'refund.processed',
            'contains' => ['refund'],
            'payload' => [
                'refund' => [
                    'entity' => [
                        'entity'          => 'refund',
                        'amount'          => 3471,
                        'currency'        => 'INR',
                        'notes'           => [],
                        'receipt'         => null,
                        'status'          => 'processed',
                        'speed_requested' => 'optimum',
                        'speed_processed' => 'instant',
                        'acquirer_data'   => [
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testRefundProcessedNormalWebhookEventData' => [
        'mode' => 'test',
        'event' => [
            'entity' => 'event',
            'event' => 'refund.processed',
            'contains' => ['refund'],
            'payload' => [
                'refund' => [
                    'entity' => [
                        'entity'          => 'refund',
                        'amount'          => 50000,
                        'currency'        => 'INR',
                        'notes'           => [],
                        'receipt'         => null,
                        'status'          => 'processed',
                        'speed_requested' => 'normal',
                        'speed_processed' => 'normal',
                        'acquirer_data'   => [
                            'arn' => null,
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testRefundCreatedWebhookEventData' => [
        'mode' => 'test',
        'event' => [
            'entity' => 'event',
            'event' => 'refund.created',
            'contains' => ['refund'],
            'payload' => [
                'refund' => [
                    'entity' => [
                        'entity'          => 'refund',
                        'amount'          => 50000,
                        'currency'        => 'INR',
                        'notes'           => [],
                        'receipt'         => null,
                        'acquirer_data'   => [
                            'arn' => null,
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testCreateSubMerchantByAggregatorWithEmail' => [
        'request'  => [
            'url'     => '/submerchants',
            'method'  => 'POST',
            'content' => [
                'id'    => 'NewSubmerchant',
                'name'  => 'Submerchant',
                'email' => 'testsub@razorpay.com',
            ],
        ],
        'response' => [
            'content' => [
                'id'               => 'acc_NewSubmerchant',
                'name'             => 'Submerchant',
                'email'            => 'testsub@razorpay.com',
                'details'          => [
                    'activation_status' => null,
                ],
                'user'             => [
                    'email'     => 'testsub@razorpay.com',
                    'confirmed' => false,
                ],
                'dashboard_access' => true,
                'pricing_plan_id'  => \RZP\Tests\Functional\Fixtures\Entity\Pricing::DEFAULT_PRICING_PLAN_ID,
            ],
        ],
    ],

    'testRefundCreatedWebhookForAggregatorModel' => [
        'mode' => 'test',
        'event' => [
            'entity' => 'event',
            'event' => 'refund.created',
            'contains' => ['refund'],
            'payload' => [
                'refund' => [
                    'entity' => [
                        'entity'          => 'refund',
                        'amount'          => 25000,
                        'currency'        => 'INR',
                        'notes'           => [],
                        'receipt'         => null,
                        'acquirer_data'   => [
                            'arn' => null,
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testTerminalOnboardingVerificationWebhook' => [
        'request' => [
            'url'     => '/terminals/onboard/verification',
            'content' => [
                'count'    => 100,
            ],
            'method'  => 'POST',
        ],
        'response'  => [
            'content'      => [],
            'status_code'  => 200,
        ],
    ],

    'testTerminalOnboardingVerificationWebhookData' => [
        'mode' => 'test',
        'event' => [
            'entity' => 'event',
            'event' => 'terminal.activated',
            'contains' => ['terminal'],
            'payload' => [
                'terminal' => [
                    'entity' => [
                        'entity'            => 'terminal',
                        'status'            => 'activated',
                        'enabled'           =>  true,
                    ],
                ],
            ],
        ],
    ],

    'testTerminalOnboardingCreationFailedWebhook' => [
        'request' => [
            'url'     => '/terminals/onboard/creation',
            'content' => [
                'count'    => 100,
            ],
            'method'  => 'POST',
        ],
        'response'  => [
            'content'      => [],
            'status_code'  => 200,
        ],
    ],

    'testTerminalOnboardingCreationFailedWebhookData' => [
        'mode' => 'test',
        'event' => [
            'entity' => 'event',
            'event' => 'terminal.failed',
            'contains' => ['terminal'],
            'payload' => [
                'terminal' => [
                    'entity' => [
                        'entity'            => 'terminal',
                        'status'            => 'failed',
                        'enabled'           =>  false,
                        'error_code'        => 'SERVER_ERROR_TERMINAL_ONBOARDING_FAILED',
                        'error_description' => 'Duplicate MVISAPAN',
                    ],
                ],
            ],
        ],
    ],

    'testTerminalOnboardingActivationFailedWebhook' => [
        'request' => [
            'url'     => '/terminals/onboard/verification',
            'method'  => 'POST',
        ],
        'response'  => [
            'content'      => [],
            'status_code'  => 200,
        ],
    ],

    'testTerminalOnboardingActivationFailedWebhookData' => [
        'mode' => 'test',
        'event' => [
            'entity' => 'event',
            'event' => 'terminal.failed',
            'contains' => ['terminal'],
            'payload' => [
                'terminal' => [
                    'entity' => [
                        'entity'            => 'terminal',
                        'status'            => 'failed',
                        'enabled'           =>  false,
                        'error_code'        => 'SERVER_ERROR_TERMINAL_ONBOARDING_FAILED',
                    ],
                ],
            ],
        ],
    ],

    'testWebhookDeactivate' => [
        'request' => [
            'url' => '',
            'content' => [
                'mode' => 'test'
            ],
            'method' => 'POST',
        ],
        'response' => [
            'content' => [],
            'status_code' => 200,
        ],
    ],

    'testWebhookDeactivateData' => [
        'subject' => 'Razorpay | Webhook deactivated after 24 hours from last successful delivery for Test Merchant',
        'mode' => 'test',
        'url' => 'http://webhook.com/v1/dummy/route',
    ],

    'createSettingsForWebhookTranslateUrl' => [
        'request'  => [
            'url'     => '/settings/partner',
            'method'  => 'post',
            'content' => [
                'translate_webhook_gateway'       => 'facebook',
            ]
        ],
        'response' => [
            'content' => [
                'success' => true
            ],
        ],
    ],
];
