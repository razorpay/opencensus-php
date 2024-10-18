<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
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

    'test1ccOrderPaidWebhookEventData' => [
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
                        'notes'           => [],
                        'line_items_total' => 50000
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

    'testOrderPaidWebhookEventDataWithTaxInvoiceBlock' => [
        'mode' => 'test',
        'event' => [
            'entity' => 'event',
            'event' => 'order.paid',
            'contains' => ['payment', 'order'],
            'payload' => [
                'order' => [
                    'entity' => [
                        'entity'          => 'order',
                        'amount'          => 50000,
                        'amount_paid'     => 50000,
                        'amount_due'      => 0,
                        'receipt'         => 'random',
                        'currency'        => 'INR',
                        'status'          => 'paid',
                        'attempts'        => 1,
                        'notes'           => [],
                        'tax_invoice'         => [
                            'business_gstin'=> '123456789012345',
                            'gst_amount'    =>  10000,
                            'supply_type'   => 'intrastate',
                            'cess_amount'   =>  12500,
                            'customer_name' => 'Gaurav',
                            'number'        => '1234',
                            "date"          => "1589994898",
                        ]
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

    'testCreateWebhookWithEventWhenFeatureNotEnabled' => [
        'request' => [
            'url'       => '/webhooks',
            'content'   => [
                'url'       => 'http://webhook.com',
                'events'    => [
                    'payment.authorized'   => '1',
                    'subscription.charged' => '1',
                ],
            ],
            'method'    => 'POST',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Invalid event name/names: subscription.charged'
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testWebhooksFeatureBasedEvents' => [
        'request' => [
            'url'       => '/webhooks',
            'content'   => [
                'url'       => 'http://webhook.com',
                'events'    => [
                    'payment.authorized'   => '1',
                    'subscription.charged' => '1',
                ],
            ],
            'method'    => 'POST',
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testWebhookCreationWithRequestHeaderMerchantFlag' => [
        'request' => [
            'url'       => '/webhooks',
            'content'   => [
                'url'       => 'http://webhook.com',
                'events'    => [
                    'payment.authorized'   => '1',
                ],
            ],
            'method'    => 'POST',
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testWebhookCreationWithoutRequestHeader' => [
        'request' => [
            'url'       => '/webhooks',
            'content'   => [
                'url'       => 'http://webhook.com',
                'events'    => [
                    'payment.authorized'   => '1',
                ],
            ],
            'method'    => 'POST',
        ],
        'response' => [
            'content' => [
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
            'contains' => ['refund', 'payment'],
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
                'payment' => [
                    'entity' => [
                        'entity' => 'payment',
                        'amount' => 50000,
                        'currency' => 'INR',
                        'status' => 'captured',
                        'amount_refunded' => 3470,
                        'refund_status' => 'partial',
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

    'refundSpeedChangedWebhookEventDataForPublicStatusFeatureEnabled' => [
        'mode' => 'test',
        'event' => [
            'entity' => 'event',
            'event' => 'refund.speed_changed',
            'contains' => ['refund', 'payment'],
            'payload' => [
                'refund' => [
                    'entity' => [
                        'entity'          => 'refund',
                        'amount'          => 3470,
                        'currency'        => 'INR',
                        'notes'           => [],
                        'receipt'         => null,
                        'status'          => 'pending',
                        'speed_requested' => 'optimum',
                        'speed_processed' => 'normal',
                        'acquirer_data'   => [
                            'arn' => null,
                        ],
                    ],
                ],
                'payment' => [
                    'entity' => [
                        'entity' => 'payment',
                        'amount' => 50000,
                        'currency' => 'INR',
                        'status' => 'captured',
                        'amount_refunded' => 3470,
                        'refund_status' => 'partial',
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

    'testRefundFailedWebhookEventData' => [
        'mode' => 'test',
        'event' => [
            'entity' => 'event',
            'event' => 'refund.failed',
            'contains' => ['refund', 'payment'],
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

    'testRefundProcessedInstantWebhookEventData' => [
        'mode' => 'test',
        'event' => [
            'entity' => 'event',
            'event' => 'refund.processed',
            'contains' => ['refund', 'payment'],
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
                'payment' => [
                    'entity' => [
                        'entity' => 'payment',
                        'amount' => 50000,
                        'currency' => 'INR',
                        'status' => 'captured',
                        'amount_refunded' => 3471,
                        'refund_status' => 'partial',
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

    'testRefundProcessedNormalWebhookEventData' => [
        'mode' => 'test',
        'event' => [
            'entity' => 'event',
            'event' => 'refund.processed',
            'contains' => ['refund', 'payment'],
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
                'payment' => [
                    'entity' => [
                        'entity' => 'payment',
                        'amount' => 50000,
                        'currency' => 'INR',
                        'status' => 'refunded',
                        'amount_refunded' => 50000,
                        'refund_status' => 'full',
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

    'testRefundProcessedWebhookOnSpeedChangeFromOptimumToNormal' => [
        'mode' => 'test',
        'event' => [
            'entity' => 'event',
            'event' => 'refund.processed',
            'contains' => ['refund', 'payment'],
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
                'payment' => [
                    'entity' => [
                        'entity' => 'payment',
                        'amount' => 50000,
                        'currency' => 'INR',
                        'status' => 'captured',
                        'amount_refunded' => 3470,
                        'refund_status' => 'partial',
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

    'testRefundCreatedWebhookEventData' => [
        'mode' => 'test',
        'event' => [
            'entity' => 'event',
            'event' => 'refund.created',
            'contains' => ['refund', 'payment'],
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
                'payment' => [
                    'entity' => [
                        'entity' => 'payment',
                        'amount' => 50000,
                        'currency' => 'INR',
                        'status' => 'refunded',
                        'amount_refunded' => 50000,
                        'refund_status' => 'full',
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

    'testCreateMalaysianSubMerchantByAggregatorWithEmail' => [
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

    'testTerminalCreatedReminderWebhook' => [
        'request' => [
            'url' => '/reminders/send/test/terminal/terminal_created_webhook/<terminalId>',
            'method' => 'post',
            'content' => [
            ]
        ],
        'response' => [
            'content'  => [
                'success' => true
            ]
        ]
    ],

    'testTerminalCreatedReminderWebhookData' => [
        'event' => [
            'entity' => 'event',
            'event' => 'terminal.created',
            'contains' => ['terminal'],
            'payload' => [
                'terminal' => [
                    'entity' => [
                        'entity'            => 'terminal',
                        'status'            => 'activated',
                        'enabled'           =>  true,
                        'mpan' => [
                            'mc_mpan'    => '1234567890123456',
                            'rupay_mpan' => '1234123412341234',
                            'visa_mpan'  => '9876543210123456',
                        ],
                        'notes' =>  null,
                    ],
                ],
            ],
        ],
    ],

    'testTerminalOnboardingStatusActivatedWebhook'  =>  [
        'request' => [
            'url'     => '/terminals/bulk',
            'method'  => 'PATCH',
        ],
        'response'  => [
            'content'      => [],
            'status_code'  => 200,
        ],
    ],

    'testTerminalOnboardingStatusActivatedWebhookData'  =>  [
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

    'testPaymentWebhookShouldNotHaveTerminalIdData' =>  [
        'mode' => 'test',
        'event' => [
            'entity' => 'event',
            'event' => 'payment.authorized',
            'contains' => ['payment'],
            'payload' => [
                'payment' => [
                    'entity' => [
                        'entity'            => 'payment',
                        'status'            => 'authorized',
                    ],
                ],
            ],
        ],
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

    'testRefundArnUpdatedWebhookEventData' => [
        'mode' => 'test',
        'event' => [
            'entity' => 'event',
            'event' => 'refund.arn_updated',
            'contains' => ['refund', 'payment'],
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
                    ],
                ],
                'payment' => [
                    'entity' => [
                        'entity' => 'payment',
                        'amount' => 50000,
                        'currency' => 'INR',
                        'status' => 'refunded',
                        'amount_refunded' => 50000,
                        'refund_status' => 'full',
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

    'testOrderPaidEventDataForMerchantsLinkedToPartner' => [
        'mode' => 'test',
        'event' => [
            'entity' => 'event',
            'event' => 'order.paid',
            'contains' => ['payment', 'order'],
            'context' => [
                'event_type' => 'partnership',
                'entity_type' => 'payment'
            ],
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

    'testOrderPaidWebhookEventPayloadWithTransactionIsolationExpDisabled' => [
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

    'testWebhookEventFireForSubscriptions' => [
        'request' => [
            'method' => 'POST',
            'content' => [
                'payload' => [
                    'subscription' => [
                        'entity' => [
                            "id" => "sub_F5aa7VaVXtXh80",
                            "quantity" => 1,
                            "notes"    => [],
                            "entity"   => "subscription",
                            "has_scheduled_changes" => false,
                            "change_scheduled_at" => null,
                            "source" => "api",
                        ]
                    ]
                ]
            ]
        ],
        'response' => [
            'content' => []
        ],
        'event' => [
            'entity' => 'event',
            'event' => 'subscription.authenticated',
            'contains' => ['subscription'],
            'payload' => [
                'subscription' => [
                    'entity' => [
                        "id"       => "sub_F5aa7VaVXtXh80",
                        "quantity" => 1,
                        "notes"    => [],
                        "entity"   => "subscription",
                        "has_scheduled_changes" => false,
                        "change_scheduled_at" => null,
                        "source" => "api",
                    ]
                ],
            ],
        ],
    ],

    'testRefundProcessedEventDataForMerchantsLinkedToPartner' =>  [
        'mode' => 'test',
        'event' => [
            'entity' => 'event',
            'event' => 'payment.captured',
            'contains' => ['payment'],
            'payload' => [
                'payment' => [
                    'entity' => [
                        'entity'            => 'payment',
                        'status'            => 'captured',
                        'captured'          => true,
                    ],
                ],
            ],
        ],
    ],

    'testInvoicePartiallyPaidEventWithTransactionIsolation' => [
        'entity'   => 'event',
        'event'    => 'invoice.partially_paid',
        'contains' => [
            'payment',
            'order',
            'invoice',
        ],
        'payload'  => [
            'payment' => [
                'entity' => [
                    'entity'     => 'payment',
                    'amount'     => 60000,
                    'currency'   => 'INR',
                    'status'     => 'captured',
                    'order_id'   => 'order_100000000order',
                    'invoice_id' => 'inv_1000000invoice',
                    'captured'   => true,
                ],
            ],
            'order' => [
                'entity' => [
                    'id'          => 'order_100000000order',
                    'entity'      => 'order',
                    'amount'      => 100000,
                    'amount_paid' => 60000,
                    'amount_due'  => 40000,
                    'currency'    => 'INR',
                    'status'      => 'attempted',
                    'attempts'    => 1,
                ],
            ],
            'invoice' => [
                'entity' => [
                    'id'              => 'inv_1000000invoice',
                    'entity'          => 'invoice',
                    'order_id'        => 'order_100000000order',
                    'status'          => 'partially_paid',
                    'partial_payment' => true,
                    'gross_amount'    => 100000,
                    'tax_amount'      => 0,
                    'amount'          => 100000,
                    'amount_paid'     => 60000,
                    'amount_due'      => 40000,
                    'currency'        => 'INR',
                ],
            ],
        ],
    ],

    'testInvoiceExpiredEventWithTransactionIsolation' => [
        'event' => [
            'entity'   => 'event',
            'event'    => 'invoice.expired',
            'contains' => [
                'invoice',
            ],
            'payload'  => [
                'invoice' => [
                    'entity' => [
                        'id'               => 'inv_1000000invoice',
                        'entity'           => 'invoice',
                        'receipt'          => null,
                        'invoice_number'   => null,
                        'customer_id'      => 'cust_100000customer',
                        'customer_details' => [
                            'name'             => 'test',
                            'email'            => 'test@razorpay.com',
                            'contact'          => '1234567890',
                            'billing_address'  => null,
                            'customer_name'    => 'test',
                            'customer_email'   => 'test@razorpay.com',
                            'customer_contact' => '1234567890',
                        ],
                        'order_id'              => 'order_100000000order',
                        'payment_id'            => null,
                        'status'                => 'expired',
                        'paid_at'               => null,
                        'cancelled_at'          => null,
                        'sms_status'            => 'sent',
                        'email_status'          => 'sent',
                        'date'                  => null,
                        'terms'                 => null,
                        'partial_payment'       => false,
                        'gross_amount'          => 100000,
                        'tax_amount'            => 0,
                        'amount'                => 100000,
                        'amount_paid'           => 0,
                        'amount_due'            => 1000000,
                        'currency'              => 'INR',
                        'description'           => null,
                        'notes'                 => [],
                        'comment'               => null,
                        'short_url'             => 'http://bitly.dev/2eZ11Vn',
                        'view_less'             => true,
                        'billing_start'         => null,
                        'billing_end'           => null,
                        'type'                  => 'invoice',
                        'group_taxes_discounts' => false,
                        'user_id'               => null,
                    ],
                ],
            ],
        ],
        'request' => [
            'url'     => '/invoices/expire',
            'method'  => 'post',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'total_invoices_count' => 1,
                'failed_invoice_ids'   => [],
                // 'time_taken'           => '1 secs',
            ],
        ]
    ],

    'testFailedPaymentWebhookWithTransactionIsolation' => [
        'event' => [
            'entity' => 'event',
            'contains' => ['payment'],
            'event' => 'payment.failed',
            'payload' => [
                'payment' => [
                    'entity' => [
                        'entity' => 'payment',
                        'status' => 'failed',
                        'captured' => false,
                    ]
                ]
            ]
        ],
        'payment_response' => [
            'response' => [
                'content' => [
                    'error' => [
                        'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                        'description' => PublicErrorDescription::BAD_REQUEST_PAYMENT_FAILED,
                    ],
                ],
                'status_code' => 400,
            ],
            'exception' => [
                'class' => 'RZP\Exception\GatewayErrorException',
                'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
                'gateway_error_code'  => null
            ],
        ],
    ],
];
