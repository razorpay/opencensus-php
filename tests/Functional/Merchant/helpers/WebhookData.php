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
                'url' => 'http://example.com',
                'events' => [
                    'payment.authorized' => true,
                ],
                'active' => true,
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
                    'description' => 'URL must point to a public IP address'
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
                    'description' => 'URL must point to a public IP address'
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
                            'url' => 'http://example.com/v1/dummy/route',
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
                'url' => 'http://random2.com',
                'events' => [
                    'payment.authorized' => false,
                ],
                'active' => false,
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
        'url' => 'http://example.com/v1/dummy/route',
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
        'url' => 'http://example.com/v1/dummy/route',
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
        'url' => 'http://example.com/v1/dummy/route',
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
            'entity'    => 'event',
            'event'     => 'settlement.processed',
            'contains' => [
                'settlement'
            ],
            'payload' => [
                'settlement' => [
                    'entity' => [
                        'entity' => 'settlement',
                        'amount' => 2500
                    ]
                ]
            ],
        ]
    ]
];
