<?php

use Carbon\Carbon;
use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testCreateOrderForRefundCreditAddition' => [
        'request' => [
            'content' => [
                "type" =>  "refund_credit",
                "method"=> "online_payment",
                "amount"=> 10000
            ],
            'method'    => 'POST',
            'url'       => '/fund_addition/initialize',
        ],
        'response' => [
            'content' => [
            ],
            'status_code' => 200,

        ],
    ],
    'testCreateOrderForFeeCreditAddition' => [
        'request' => [
            'content' => [
                "type" =>  "fee_credit",
                "method"=> "online_payment",
                "amount"=> 10000
            ],
            'method'    => 'POST',
            'url'       => '/fund_addition/initialize',
        ],
        'response' => [
            'content' => [
            ],
            'status_code' => 200,

        ],
    ],
    'testCreateOrderForReserveBalanceAddition' => [
        'request' => [
            'content' => [
                "type" =>  "reserve_balance",
                "method"=> "online_payment",
                "amount"=> 10000
            ],
            'method'    => 'POST',
            'url'       => '/fund_addition/initialize',
        ],
        'response' => [
            'content' => [
            ],
            'status_code' => 200,

        ],
    ],
    'testCreateOrderAndMakePaymentForFeeCreditAddition' => [
        'request' => [
            'content' => [
                "type" =>  "refund_credit",
                "method"=> "online_payment",
                "amount"=> 50000
            ],
            'method'    => 'POST',
            'url'       => '/fund_addition/initialize',
        ],
        'response' => [
            'content' => [
            ],
            'status_code' => 200,
        ],
    ],
    'testTpvPaymentEntity' => [
        'amount'          => 500,
        'action'          => 'authorize',
        'bank'            => 'IDFB',
        'bank_payment_id' => '9999999999',
        'status'          => 'SUC000',
        'reference1'      => null,
        'received'        => true,
    ],
    'testCreateOrder' => [
        'request' => [
            'content' => [
                'amount'        => 50000,
                'currency'      => 'INR',
                'notes' => [
                    "merchant_id" => '10000000000000',
                    "type" => 'refund_credit'
                ],
                'payment_capture' => 1
            ],
            'method'    => 'POST',
            'url'       => '/orders',
        ],
        'response' => [
            'content' => [
                'amount'        => 50000,
                'currency'      => 'INR',
                'notes' => [
                    "merchant_id" => '10000000000000',
                    "type" => 'refund_credit'
                ]
            ],
            'status_code' => 200,
        ],
    ],
    'testCreateOrderAndMakePaymentForRefundCreditAdditionWithWebhook' => [
        'request' => [
            'content' => [
                "type" =>  "refund_credit",
                "method"=> "online_payment",
                "amount"=> 50000
            ],
            'method'    => 'POST',
            'url'       => '/fund_addition/initialize',
        ],
        'response' => [
            'content' => [
            ],
            'status_code' => 200,
        ],
    ],
    'orderPaidWebhookEventData' => [
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
                        'notes'           => [
                            'merchant_id' => '10000000000000',
                            'type'        => 'refund_credit'
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
    'testTpvPaymentEntity' => [
        'amount'          => 500,
        'action'          => 'authorize',
        'bank'            => 'IDFB',
        'bank_payment_id' => '9999999999',
        'status'          => 'SUC000',
        'reference1'      => null,
        'received'        => true,
    ],
    'testCreateOrder' => [
        'request' => [
            'content' => [
                'amount'        => 50000,
                'currency'      => 'INR',
                'notes' => [
                    "merchant_id" => '10000000000000',
                    "type" => 'refund_credit'
                ],
                'payment_capture' => 1
            ],
            'method'    => 'POST',
            'url'       => '/orders',
        ],
        'response' => [
            'content' => [
                'amount'        => 50000,
                'currency'      => 'INR',
                'notes' => [
                    "merchant_id" => '10000000000000',
                    "type" => 'refund_credit'
                ]
            ],
            'status_code' => 200,
        ],
    ],
    'testFundAdditionWebhookWithInvalidOrderStatusForRefundcredits' => [
        'request'   => [
            'content' => [
                "entity" => "event",
                "account_id" => "acc_BFQ7uQEaa7j2z7",
                "event" => "order.paid",
                "contains" => [
                   "payment",
                   "order"
                ],
              "payload"=> [
                "payment" => [
                    "entity"=> [
                        "id" => "pay_DESlfW9H8K9uqM",
                        "entity" => "payment",
                        "amount" => 100,
                        "currency" => "INR",
                        "status" => "captured",
                        "order_id" => "order_DESlLckIVRkHWj",
                        "invoice_id" => null,
                        "international" =>  false,
                        "method" => "netbanking",
                        "amount_refunded" => 0,
                        "refund_status"=> null,
                        "captured" => true,
                        "description" => null,
                        "card_id" => null,
                        "bank" => "HDFC",
                        "wallet" =>  null,
                        "vpa" => null,
                        "email" => "gaurav.kumar@example.com",
                        "contact" => "+919876543210",
                        "notes" => [
                            "merchant_id" => "10000000000000",
                            "type" => 'refund_credit'
                        ],
                        "fee" => 2,
                        "tax" =>  0,
                        "error_code" => null,
                        "error_description"=>  null,
                        "created_at" => 1567674599
                  ]
                ],
                "order" => [
                    "entity" => [
                        "id" => "order_DESlLckIVRkHWj",
                        "entity" =>  "order",
                        "amount" => 100,
                        "amount_paid" => 100,
                        "amount_due" => 0,
                        "currency" => "INR",
                        "receipt" => "rcptid #1",
                        "offer_id" =>  null,
                        "status" => "paid",
                        "attempts" => 1,
                        "notes" => [
                            "merchant_id" => '10000000000001',
                            'type' => 'refund_credit'
                        ],
                        "created_at" => 1567674581
                  ]
                ]
              ],
              "created_at"=> 1567674606
            ],
            'method'  => 'POST',
            'url'     => '/fund_addition/online_payment/webhook',
        ],
        'response'  => [
            'content'     => [
                "error_code" => ErrorCode::BAD_REQUEST_ORDER_STATUS_INVALID_FOR_FUND_ADDITION
            ],
            'status_code' => 200,
        ],
    ],
    'testFundAdditionWebhookWithInvalidOrderStatusForFeeCredits' => [
        'request'   => [
            'content' => [
                "entity" => "event",
                "account_id" => "acc_BFQ7uQEaa7j2z7",
                "event" => "order.paid",
                "contains" => [
                    "payment",
                    "order"
                ],
                "payload"=> [
                    "payment" => [
                        "entity"=> [
                            "id" => "pay_DESlfW9H8K9uqM",
                            "entity" => "payment",
                            "amount" => 100,
                            "currency" => "INR",
                            "status" => "captured",
                            "order_id" => "order_DESlLckIVRkHWj",
                            "invoice_id" => null,
                            "international" =>  false,
                            "method" => "netbanking",
                            "amount_refunded" => 0,
                            "refund_status"=> null,
                            "captured" => true,
                            "description" => null,
                            "card_id" => null,
                            "bank" => "HDFC",
                            "wallet" =>  null,
                            "vpa" => null,
                            "email" => "gaurav.kumar@example.com",
                            "contact" => "+919876543210",
                            "notes" => [
                                "merchant_id" => "10000000000000",
                                "type" => 'refund_credit'
                            ],
                            "fee" => 2,
                            "tax" =>  0,
                            "error_code" => null,
                            "error_description"=>  null,
                            "created_at" => 1567674599
                        ]
                    ],
                    "order" => [
                        "entity" => [
                            "id" => "order_DESlLckIVRkHWj",
                            "entity" =>  "order",
                            "amount" => 100,
                            "amount_paid" => 100,
                            "amount_due" => 0,
                            "currency" => "INR",
                            "receipt" => "rcptid #1",
                            "offer_id" =>  null,
                            "status" => "paid",
                            "attempts" => 1,
                            "notes" => [
                                "merchant_id" => '10000000000000',
                                'type' => 'fee_credit'
                            ],
                            "created_at" => 1567674581
                        ]
                    ]
                ],
                "created_at"=> 1567674606
            ],
            'method'  => 'POST',
            'url'     => '/fund_addition/online_payment/webhook',
        ],
        'response'  => [
            'content'     => [
                "error_code" => ErrorCode::BAD_REQUEST_ORDER_STATUS_INVALID_FOR_FUND_ADDITION
            ],
            'status_code' => 200,
        ]
    ],
    'testFundAdditionWebhookWithInvalidPaymentIdForRefundCredits' => [
        'request'   => [
            'content' => [
                "entity" => "event",
                "account_id" => "acc_BFQ7uQEaa7j2z7",
                "event" => "order.paid",
                "contains" => [
                    "payment",
                    "order"
                ],
                "payload"=> [
                    "payment" => [
                        "entity"=> [
                            "id" => "pay_DESlfW9H8K9uqM",
                            "entity" => "payment",
                            "amount" => 100,
                            "currency" => "INR",
                            "status" => "captured",
                            "order_id" => "order_DESlLckIVRkHWj",
                            "invoice_id" => null,
                            "international" =>  false,
                            "method" => "netbanking",
                            "amount_refunded" => 0,
                            "refund_status"=> null,
                            "captured" => true,
                            "description" => null,
                            "card_id" => null,
                            "bank" => "HDFC",
                            "wallet" =>  null,
                            "vpa" => null,
                            "email" => "gaurav.kumar@example.com",
                            "contact" => "+919876543210",
                            "notes" => [
                                "merchant_id" => "10000000000000",
                                "type" => 'refund_credit'
                            ],
                            "fee" => 2,
                            "tax" =>  0,
                            "error_code" => null,
                            "error_description"=>  null,
                            "created_at" => 1567674599
                        ]
                    ],
                    "order" => [
                        "entity" => [
                            "id" => "order_DESlLckIVRkHWj",
                            "entity" =>  "order",
                            "amount" => 100,
                            "amount_paid" => 100,
                            "amount_due" => 0,
                            "currency" => "INR",
                            "receipt" => "rcptid #1",
                            "offer_id" =>  null,
                            "status" => "paid",
                            "attempts" => 1,
                            "notes" => [
                                "merchant_id" => '10000000000000',
                                'type' => 'refund_credit'
                            ],
                            "created_at" => 1567674581
                        ]
                    ]
                ],
                "created_at"=> 1567674606
            ],
            'method'  => 'POST',
            'url'     => '/fund_addition/online_payment/webhook',
        ],
        'response'  => [
            'content'     => [
                "error_code" => ErrorCode::BAD_REQUEST_INVALID_ORDER_ID_IN_PAYMENT
            ],
            'status_code' => 200,
        ],
    ],
    'testFundAdditionWebhookWithTamperedAmountForRefundCredits' => [
        'request'   => [
            'content' => [
                "entity" => "event",
                "account_id" => "acc_BFQ7uQEaa7j2z7",
                "event" => "order.paid",
                "contains" => [
                    "payment",
                    "order"
                ],
                "payload"=> [
                    "payment" => [
                        "entity"=> [
                            "id" => "pay_DESlfW9H8K9uqM",
                            "entity" => "payment",
                            "amount" => 100,
                            "currency" => "INR",
                            "status" => "captured",
                            "order_id" => "order_DESlLckIVRkHWj",
                            "invoice_id" => null,
                            "international" =>  false,
                            "method" => "netbanking",
                            "amount_refunded" => 0,
                            "refund_status"=> null,
                            "captured" => true,
                            "description" => null,
                            "card_id" => null,
                            "bank" => "HDFC",
                            "wallet" =>  null,
                            "vpa" => null,
                            "email" => "gaurav.kumar@example.com",
                            "contact" => "+919876543210",
                            "notes" => [
                                "merchant_id" => "10000000000001",
                                "type" => 'refund_credit'
                            ],
                            "fee" => 1426,
                            "tax" =>  0,
                            "error_code" => null,
                            "error_description"=>  null,
                            "created_at" => 1567674599
                        ]
                    ],
                    "order" => [
                        "entity" => [
                            "id" => "order_DESlLckIVRkHWj",
                            "entity" =>  "order",
                            "amount" => 100,
                            "amount_paid" => 100,
                            "amount_due" => 0,
                            "currency" => "INR",
                            "receipt" => "rcptid #1",
                            "offer_id" =>  null,
                            "status" => "paid",
                            "attempts" => 1,
                            "notes" => [
                                "merchant_id" => '10000000000001',
                                'type' => 'refund_credit'
                            ],
                            "created_at" => 1567674581
                        ]
                    ]
                ],
                "created_at"=> 1567674606
            ],
            'method'  => 'POST',
            'url'     => '/fund_addition/online_payment/webhook',
        ],
        'response'  => [
            'content'     => [
                'error_code' => ErrorCode::BAD_REQUEST_PAYMENT_DATA_TAMPERED

            ],
            'status_code' => 200,
        ],
    ],
    'testFundAdditionWebhookWithFundAlreadyAddedForOrder' => [
        'request'   => [
            'content' => [
                "entity" => "event",
                "account_id" => "acc_BFQ7uQEaa7j2z7",
                "event" => "order.paid",
                "contains" => [
                    "payment",
                    "order"
                ],
                "payload"=> [
                    "payment" => [
                        "entity"=> [
                            "id" => "pay_DESlfW9H8K9uqM",
                            "entity" => "payment",
                            "amount" => 50000,
                            "currency" => "INR",
                            "status" => "captured",
                            "order_id" => "order_DESlLckIVRkHWj",
                            "invoice_id" => null,
                            "international" =>  false,
                            "method" => "netbanking",
                            "amount_refunded" => 0,
                            "refund_status"=> null,
                            "captured" => true,
                            "description" => null,
                            "card_id" => null,
                            "bank" => "HDFC",
                            "wallet" =>  null,
                            "vpa" => null,
                            "email" => "gaurav.kumar@example.com",
                            "contact" => "+919876543210",
                            "notes" => [
                                "merchant_id" => "10000000000000",
                                "type" => 'refund_credit'
                            ],
                            "fee" => 1476,
                            "tax" =>  0,
                            "error_code" => null,
                            "error_description"=>  null,
                            "created_at" => 1567674599
                        ]
                    ],
                    "order" => [
                        "entity" => [
                            "id" => "order_DESlLckIVRkHWj",
                            "entity" =>  "order",
                            "amount" => 100,
                            "amount_paid" => 100,
                            "amount_due" => 0,
                            "currency" => "INR",
                            "receipt" => "rcptid #1",
                            "offer_id" =>  null,
                            "status" => "paid",
                            "attempts" => 1,
                            "notes" => [
                                "merchant_id" => '10000000000000',
                                'type' => 'refund_credit'
                            ],
                            "created_at" => 1567674581
                        ]
                    ]
                ],
                "created_at"=> 1567674606
            ],
            'method'  => 'POST',
            'url'     => '/fund_addition/online_payment/webhook',
        ],
        'response'  => [
            'content'     => [
                'error_code' => ErrorCode::BAD_REQUEST_CREDITS_ALREADY_ADDED_FOR_THE_GIVEN_CAMPAIGN
            ],
            'status_code' => 200,
        ],
    ],
    'testFundAdditionWebhookWithFundAlreadyAddedForReserveBalance' => [
        'request'   => [
            'content' => [
                "entity" => "event",
                "account_id" => "acc_BFQ7uQEaa7j2z7",
                "event" => "order.paid",
                "contains" => [
                    "payment",
                    "order"
                ],
                "payload"=> [
                    "payment" => [
                        "entity"=> [
                            "id" => "pay_DESlfW9H8K9uqM",
                            "entity" => "payment",
                            "amount" => 50000,
                            "currency" => "INR",
                            "status" => "captured",
                            "order_id" => "order_DESlLckIVRkHWj",
                            "invoice_id" => null,
                            "international" =>  false,
                            "method" => "netbanking",
                            "amount_refunded" => 0,
                            "refund_status"=> null,
                            "captured" => true,
                            "description" => null,
                            "card_id" => null,
                            "bank" => "HDFC",
                            "wallet" =>  null,
                            "vpa" => null,
                            "email" => "gaurav.kumar@example.com",
                            "contact" => "+919876543210",
                            "notes" => [
                                "merchant_id" => "10000000000000",
                                "type" => 'reserve_balance'
                            ],
                            "fee" => 1476,
                            "tax" =>  0,
                            "error_code" => null,
                            "error_description"=>  null,
                            "created_at" => 1567674599
                        ]
                    ],
                    "order" => [
                        "entity" => [
                            "id" => "order_DESlLckIVRkHWj",
                            "entity" =>  "order",
                            "amount" => 100,
                            "amount_paid" => 100,
                            "amount_due" => 0,
                            "currency" => "INR",
                            "receipt" => "rcptid #1",
                            "offer_id" =>  null,
                            "status" => "paid",
                            "attempts" => 1,
                            "notes" => [
                                "merchant_id" => '10000000000000',
                                'type' => 'reserve_balance'
                            ],
                            "created_at" => 1567674581
                        ]
                    ]
                ],
                "created_at"=> 1567674606
            ],
            'method'  => 'POST',
            'url'     => '/fund_addition/online_payment/webhook',
        ],
        'response'  => [
            'content'     => [
                'error_code' => ErrorCode::BAD_REQUEST_RESERVE_BALANCE_ALREADY_ADDED_FOR_GIVEN_DESC
            ],
            'status_code' => 200,
        ],
    ],
    'testRefundCreditLoadingOutboxPushPGLedgerReverseShadow' => [
        'request'   => [
            'content' => [
                "entity" => "event",
                "account_id" => "acc_BFQ7uQEaa7j2z7",
                "event" => "order.paid",
                "contains" => [
                    "payment",
                    "order"
                ],
                "payload"=> [
                    "payment" => [
                        "entity"=> [
                            "id" => "pay_DESlfW9H8K9uqM",
                            "entity" => "payment",
                            "amount" => 50000,
                            "currency" => "INR",
                            "status" => "captured",
                            "order_id" => "order_DESlLckIVRkHWj",
                            "invoice_id" => null,
                            "international" =>  false,
                            "method" => "netbanking",
                            "amount_refunded" => 0,
                            "refund_status"=> null,
                            "captured" => true,
                            "description" => null,
                            "card_id" => null,
                            "bank" => "HDFC",
                            "wallet" =>  null,
                            "vpa" => null,
                            "email" => "gaurav.kumar@example.com",
                            "contact" => "+919876543210",
                            "notes" => [
                                "merchant_id" => "10000000000001",
                                "type" => 'refund_credit'
                            ],
                            "fee" => 1476,
                            "tax" =>  0,
                            "error_code" => null,
                            "error_description"=>  null,
                            "created_at" => 1567674599
                        ]
                    ],
                    "order" => [
                        "entity" => [
                            "id" => "order_DESlLckIVRkHWj",
                            "entity" =>  "order",
                            "amount" => 100,
                            "amount_paid" => 100,
                            "amount_due" => 0,
                            "currency" => "INR",
                            "receipt" => "rcptid #1",
                            "offer_id" =>  null,
                            "status" => "paid",
                            "attempts" => 1,
                            "notes" => [
                                "merchant_id" => '10000000000001',
                                'type' => 'refund_credit'
                            ],
                            "created_at" => 1567674581
                        ]
                    ]
                ],
                "created_at"=> 1567674606
            ],
            'method'  => 'POST',
            'url'     => '/fund_addition/online_payment/webhook',
        ],
        'response'  => [
            'content'     => [
            ],
            'status_code' => 200,
        ],
    ],
    'testFeeCreditLoadingOutboxPushPGLedgerReverseShadow' => [
        'request'   => [
            'content' => [
                "entity" => "event",
                "account_id" => "acc_BFQ7uQEaa7j2z7",
                "event" => "order.paid",
                "contains" => [
                    "payment",
                    "order"
                ],
                "payload"=> [
                    "payment" => [
                        "entity"=> [
                            "id" => "pay_DESlfW9H8K9uqM",
                            "entity" => "payment",
                            "amount" => 50000,
                            "currency" => "INR",
                            "status" => "captured",
                            "order_id" => "order_DESlLckIVRkHWj",
                            "invoice_id" => null,
                            "international" =>  false,
                            "method" => "netbanking",
                            "amount_refunded" => 0,
                            "refund_status"=> null,
                            "captured" => true,
                            "description" => null,
                            "card_id" => null,
                            "bank" => "HDFC",
                            "wallet" =>  null,
                            "vpa" => null,
                            "email" => "gaurav.kumar@example.com",
                            "contact" => "+919876543210",
                            "notes" => [
                                "merchant_id" => "10000000000001",
                                "type" => 'fee_credit'
                            ],
                            "fee" => 1476,
                            "tax" =>  0,
                            "error_code" => null,
                            "error_description"=>  null,
                            "created_at" => 1567674599
                        ]
                    ],
                    "order" => [
                        "entity" => [
                            "id" => "order_DESlLckIVRkHWj",
                            "entity" =>  "order",
                            "amount" => 100,
                            "amount_paid" => 100,
                            "amount_due" => 0,
                            "currency" => "INR",
                            "receipt" => "rcptid #1",
                            "offer_id" =>  null,
                            "status" => "paid",
                            "attempts" => 1,
                            "notes" => [
                                "merchant_id" => '10000000000001',
                                'type' => 'fee_credit'
                            ],
                            "created_at" => 1567674581
                        ]
                    ]
                ],
                "created_at"=> 1567674606
            ],
            'method'  => 'POST',
            'url'     => '/fund_addition/online_payment/webhook',
        ],
        'response'  => [
            'content'     => [
            ],
            'status_code' => 200,
        ],
    ],
    'addFundsViaOrderReverseShadow' => [
        'request'   => [
            'content' => [
                "entity" => "event",
                "account_id" => "acc_BFQ7uQEaa7j2z7",
                "event" => "order.paid",
                "contains" => [
                    "payment",
                    "order"
                ],
                "payload"=> [
                    "payment" => [
                        "entity"=> [
                            "id" => "pay_DESlfW9H8K9uqM",
                            "entity" => "payment",
                            "amount" => 50000,
                            "currency" => "INR",
                            "status" => "captured",
                            "order_id" => "order_DESlLckIVRkHWj",
                            "invoice_id" => null,
                            "international" =>  false,
                            "method" => "netbanking",
                            "amount_refunded" => 0,
                            "refund_status"=> null,
                            "captured" => true,
                            "description" => null,
                            "card_id" => null,
                            "bank" => "HDFC",
                            "wallet" =>  null,
                            "vpa" => null,
                            "email" => "gaurav.kumar@example.com",
                            "contact" => "+919876543210",
                            "notes" => [
                                "merchant_id" => "10000000000001",
                                "type" => 'fee_credit'
                            ],
                            "fee" => 1476,
                            "tax" =>  0,
                            "error_code" => null,
                            "error_description"=>  null,
                            "created_at" => 1567674599
                        ]
                    ],
                    "order" => [
                        "entity" => [
                            "id" => "order_DESlLckIVRkHWj",
                            "entity" =>  "order",
                            "amount" => 100,
                            "amount_paid" => 100,
                            "amount_due" => 0,
                            "currency" => "INR",
                            "receipt" => "rcptid #1",
                            "offer_id" =>  null,
                            "status" => "paid",
                            "attempts" => 1,
                            "notes" => [
                                "merchant_id" => '10000000000001',
                                'type' => 'fee_credit'
                            ],
                            "created_at" => 1567674581
                        ]
                    ]
                ],
                "created_at"=> 1567674606
            ],
            'method'  => 'POST',
            'url'     => '/fund_addition/online_payment/webhook',
        ],
        'response'  => [
            'content'     => [
            ],
            'status_code' => 200,
        ],
    ],
    'testFundAdditionWebhookWithFundAdditionInCredits' => [
        'request'   => [
            'content' => [
                "entity" => "event",
                "account_id" => "acc_BFQ7uQEaa7j2z7",
                "event" => "order.paid",
                "contains" => [
                    "payment",
                    "order"
                ],
                "payload"=> [
                    "payment" => [
                        "entity"=> [
                            "id" => "pay_DESlfW9H8K9uqM",
                            "entity" => "payment",
                            "amount" => 50000,
                            "currency" => "INR",
                            "status" => "captured",
                            "order_id" => "order_DESlLckIVRkHWj",
                            "invoice_id" => null,
                            "international" =>  false,
                            "method" => "netbanking",
                            "amount_refunded" => 0,
                            "refund_status"=> null,
                            "captured" => true,
                            "description" => null,
                            "card_id" => null,
                            "bank" => "HDFC",
                            "wallet" =>  null,
                            "vpa" => null,
                            "email" => "gaurav.kumar@example.com",
                            "contact" => "+919876543210",
                            "notes" => [
                                "merchant_id" => "10000000000001",
                                "type" => 'refund_credit'
                            ],
                            "fee" => 1476,
                            "tax" =>  0,
                            "error_code" => null,
                            "error_description"=>  null,
                            "created_at" => 1567674599
                        ]
                    ],
                    "order" => [
                        "entity" => [
                            "id" => "order_DESlLckIVRkHWj",
                            "entity" =>  "order",
                            "amount" => 100,
                            "amount_paid" => 100,
                            "amount_due" => 0,
                            "currency" => "INR",
                            "receipt" => "rcptid #1",
                            "offer_id" =>  null,
                            "status" => "paid",
                            "attempts" => 1,
                            "notes" => [
                                "merchant_id" => '10000000000001',
                                'type' => 'refund_credit'
                            ],
                            "created_at" => 1567674581
                        ]
                    ]
                ],
                "created_at"=> 1567674606
            ],
            'method'  => 'POST',
            'url'     => '/fund_addition/online_payment/webhook',
        ],
        'response'  => [
            'content'     => [
            ],
            'status_code' => 200,
        ],
    ],
    'testFundAdditionWebhookWithFundAdditionInCreditsWithEmptyNotes' => [
        'request'   => [
            'content' => [
                "entity" => "event",
                "account_id" => "acc_BFQ7uQEaa7j2z7",
                "event" => "order.paid",
                "contains" => [
                    "payment",
                    "order"
                ],
                "payload"=> [
                    "payment" => [
                        "entity"=> [
                            "id" => "pay_DESlfW9H8K9uqM",
                            "entity" => "payment",
                            "amount" => 50000,
                            "currency" => "INR",
                            "status" => "captured",
                            "order_id" => "order_DESlLckIVRkHWj",
                            "invoice_id" => null,
                            "international" =>  false,
                            "method" => "netbanking",
                            "amount_refunded" => 0,
                            "refund_status"=> null,
                            "captured" => true,
                            "description" => null,
                            "card_id" => null,
                            "bank" => "HDFC",
                            "wallet" =>  null,
                            "vpa" => null,
                            "email" => "gaurav.kumar@example.com",
                            "contact" => "+919876543210",
                            "notes" => [
                                "merchant_id" => "10000000000001",
                                "type" => 'refund_credit'
                            ],
                            "fee" => 1476,
                            "tax" =>  0,
                            "error_code" => null,
                            "error_description"=>  null,
                            "created_at" => 1567674599
                        ]
                    ],
                    "order" => [
                        "entity" => [
                            "id" => "order_DESlLckIVRkHWj",
                            "entity" =>  "order",
                            "amount" => 100,
                            "amount_paid" => 100,
                            "amount_due" => 0,
                            "currency" => "INR",
                            "receipt" => "rcptid #1",
                            "offer_id" =>  null,
                            "status" => "paid",
                            "attempts" => 1,
                            "notes" => [
                            ],
                            "created_at" => 1567674581
                        ]
                    ]
                ],
                "created_at"=> 1567674606
            ],
            'method'  => 'POST',
            'url'     => '/fund_addition/online_payment/webhook',
        ],
        'response'  => [
            'content'     => [
                'error_code' => ErrorCode::BAD_REQUEST_MERCHANT_INFO_NOT_PRESENT_FOR_FUND_ADDITION
            ],
            'status_code' => 200,
        ],
    ],
    'testVACreation' => [
        'request' => [
            'content' => [
                "type" =>  "refund_credit",
                "method"=> "account_transfer"
            ],
            'method'    => 'POST',
            'url'       => '/fund_addition/initialize',
        ],
        'response' => [
            'content' => [
            ],
            'status_code' => 200,
        ],
    ],
    'testFundAdditionWebhookWithFundAdditionInvalidType' => [
        'request' => [
            'content' => [

                    "entity" => "event",
                    "account_id" => "acc_BFQ7uQEaa7j2z7",
                    "event" => "virtual_account.credited",
                    "contains" => [
                       "payment",
                       "virtual_account",
                       "bank_transfer"
                    ],
                    "payload" => [
                       "payment" => [
                           "entity" => [
                               "id" => "pay_DETA2KrOlhqQzF",
                                "entity" => "payment",
                                "amount" => 61900,
                                "currency" => "INR",
                                "status" => "captured",
                                "order_id" => null,
                                "invoice_id" => null,
                                "international" => false,
                                "method" => "bank_transfer",
                                "amount_refunded" => 0,
                                "amount_transferred"=> 0,
                                "refund_status" => null,
                                "captured" => true,
                                "customer_id" => "cust_BtQNqzmBlAXyTY",
                                "notes" => [],
                                "fee" => 731,
                                "tax" => 112,
                                "error_code" => null,
                                "error_description" => null,
                                "created_at" => 1567675983
                           ]
                       ],
                        "virtual_account" => [
                            "entity" => [
                                "id" => "va_DET8z3wBxfPB5L",
                                "name" => "Acme Corp",
                                "entity" => "virtual_account",
                                "status" =>  "active",
                                "description" => "Virtual Account to test webhook",
                                "amount_expected" => null,
                                "notes" => [
                                    "Important" =>  "Notes for Internal Reference"
                                ],
                                "amount_paid" => 61900,
                                "customer_id" => "cust_BtQNqzmBlAXyTY",

                                ]
                            ],
                            "bank_transfer" => [
                                "entity"=> [
                                    "id" => "bt_DETA2KSUJ3uCM9",
                                    "entity" => "bank_transfer",
                                    "payment_id"=> "pay_DETA2KrOlhqQzF",
                                    "mode" => "NEFT",
                                    "bank_reference" => "156767598340",
                                    "amount" => 61900,
                                    "payer_bank_account" => [
                                          "id" => "ba_DETA2UuuKtKLR1",
                                          "entity" => "bank_account",
                                          "ifsc" =>  "KKBK0000007",
                                          "bank_name" =>  "Kotak Mahindra Bank",
                                          "name" => "Saurav Kumar",
                                          "account_number" => "765432123456789"
                                    ],
                                    "virtual_account_id" => "va_DET8z3wBxfPB5L"
                               ]

                            ],
                        "created_at"=> 1567675983
                    ]
                ],
            'method'    => 'POST',
            'url'       => '/fund_addition/account_transfer/webhook',
            ],
            'response'  => [
                'content'     => [
                    'error_code' => ErrorCode::BAD_REQUEST_MERCHANT_INFO_NOT_PRESENT_FOR_FUND_ADDITION
                ],
                'status_code' => 200,
            ],
        ],
    'fundAdditionToVirtualAccount' => [
        'request'  => [
            'url'     => '/ecollect/validate/test',
            'method'  => 'post',
            'content' => [
                'payee_account'  => null,
                'payee_ifsc'     => null,
                'payer_name'     => 'Name of account holder',
                'payer_account'  => '765432123456789',
                'payer_ifsc'     => 'HDFC0000053',
                'mode'           => 'neft',
                'transaction_id' => strtoupper(random_alphanum_string(22)),
                'time'           => 148415544000,
                'amount'         => 50000,
                'description'    => 'NEFT payment of 50,000 rupees',
            ],
        ],
        'response' => [
            'content' => [

            ],
            'status_code' => 200,
        ],
    ],
    'addFundsViaWebhook' => [
        'request'  => [
            'url'     => '/fund_addition/account_transfer/webhook',
            'method'  => 'post',
            'content' => [

            ],
        ],
        'response' => [
            'content' => [

            ],
            'status_code' => 200,
        ],
    ],
    'testFundAdditionViaWebhookWithInvalidPaymentId' => [
    'request' => [
        'content' => [

            "entity" => "event",
            "account_id" => "acc_BFQ7uQEaa7j2z7",
            "event" => "virtual_account.credited",
            "contains" => [
                "payment",
                "virtual_account",
                "bank_transfer"
            ],
            "payload" => [
                "payment" => [
                    "entity" => [
                        "id" => "pay_DETA2KrOlhqQzF",
                        "entity" => "payment",
                        "amount" => 61900,
                        "currency" => "INR",
                        "status" => "captured",
                        "order_id" => null,
                        "invoice_id" => null,
                        "international" => false,
                        "method" => "bank_transfer",
                        "amount_refunded" => 0,
                        "amount_transferred"=> 0,
                        "refund_status" => null,
                        "captured" => true,
                        "customer_id" => "cust_BtQNqzmBlAXyTY",
                        "notes" => [],
                        "fee" => 731,
                        "tax" => 112,
                        "error_code" => null,
                        "error_description" => null,
                        "created_at" => 1567675983
                    ]
                ],
                "virtual_account" => [
                    "entity" => [
                        "id" => "va_DET8z3wBxfPB5L",
                        "name" => "Acme Corp",
                        "entity" => "virtual_account",
                        "status" =>  "active",
                        "description" => "Virtual Account to test webhook",
                        "amount_expected" => null,
                        "notes" => [
                            "Important" =>  "Notes for Internal Reference"
                        ],
                        "amount_paid" => 61900,
                        "customer_id" => "cust_BtQNqzmBlAXyTY",

                    ]
                ],
                "bank_transfer" => [
                    "entity"=> [
                        "id" => "bt_DETA2KSUJ3uCM9",
                        "entity" => "bank_transfer",
                        "payment_id"=> "pay_DETA2KrOlhqQzF",
                        "mode" => "NEFT",
                        "bank_reference" => "156767598340",
                        "amount" => 61900,
                        "payer_bank_account" => [
                            "id" => "ba_DETA2UuuKtKLR1",
                            "entity" => "bank_account",
                            "ifsc" =>  "KKBK0000007",
                            "bank_name" =>  "Kotak Mahindra Bank",
                            "name" => "Saurav Kumar",
                            "account_number" => "765432123456789"
                        ],
                        "virtual_account_id" => "va_DET8z3wBxfPB5L"
                    ]

                ],
                "created_at"=> 1567675983
            ]
        ],
        'method'    => 'POST',
        'url'       => '/fund_addition/account_transfer/webhook',
    ],
    'response'  => [
        'content'     => [
            'error_code' => ErrorCode::BAD_REQUEST_BANK_TRANSFER_INPUT_DATA_TAMPERED
        ],
        'status_code' => 200,
    ],
],

    'testFundAdditionViaWebhookWithInvalidVAId' => [
        'request' => [
            'content' => [

                "entity" => "event",
                "account_id" => "acc_BFQ7uQEaa7j2z7",
                "event" => "virtual_account.credited",
                "contains" => [
                    "payment",
                    "virtual_account",
                    "bank_transfer"
                ],
                "payload" => [
                    "payment" => [
                        "entity" => [
                            "id" => "pay_DETA2KrOlhqQzF",
                            "entity" => "payment",
                            "amount" => 61900,
                            "currency" => "INR",
                            "status" => "captured",
                            "order_id" => null,
                            "invoice_id" => null,
                            "international" => false,
                            "method" => "bank_transfer",
                            "amount_refunded" => 0,
                            "amount_transferred"=> 0,
                            "refund_status" => null,
                            "captured" => true,
                            "customer_id" => "cust_BtQNqzmBlAXyTY",
                            "notes" => [],
                            "fee" => 731,
                            "tax" => 112,
                            "error_code" => null,
                            "error_description" => null,
                            "created_at" => 1567675983
                        ]
                    ],
                    "virtual_account" => [
                        "entity" => [
                            "id" => "va_DET8z3wBxfPB5L",
                            "name" => "Acme Corp",
                            "entity" => "virtual_account",
                            "status" =>  "active",
                            "description" => "Virtual Account to test webhook",
                            "amount_expected" => null,
                            "notes" => [
                                "Important" =>  "Notes for Internal Reference"
                            ],
                            "amount_paid" => 61900,
                            "customer_id" => "cust_BtQNqzmBlAXyTY",

                        ]
                    ],
                    "bank_transfer" => [
                        "entity"=> [
                            "id" => "bt_DETA2KSUJ3uCM9",
                            "entity" => "bank_transfer",
                            "payment_id"=> "pay_DETA2KrOlhqQzF",
                            "mode" => "NEFT",
                            "bank_reference" => "156767598340",
                            "amount" => 61900,
                            "payer_bank_account" => [
                                "id" => "ba_DETA2UuuKtKLR1",
                                "entity" => "bank_account",
                                "ifsc" =>  "KKBK0000007",
                                "bank_name" =>  "Kotak Mahindra Bank",
                                "name" => "Saurav Kumar",
                                "account_number" => "765432123456789"
                            ],
                            "virtual_account_id" => "va_DET8z3wBxfPB5L"
                        ]

                    ],
                    "created_at"=> 1567675983
                ]
            ],
            'method'    => 'POST',
            'url'       => '/fund_addition/account_transfer/webhook',
        ],
        'response'  => [
            'content'     => [
                'error_code' => ErrorCode::BAD_REQUEST_PAYMENT_DATA_TAMPERED
            ],
            'status_code' => 200,
        ],
    ],
    'testVACreationForReserveBalance' => [
        'request' => [
            'content' => [
                "type" =>  "reserve_balance",
                "method"=> "account_transfer"
            ],
            'method'    => 'POST',
            'url'       => '/fund_addition/initialize',
        ],
        'response' => [
            'content' => [
            ],
            'status_code' => 200,
        ],
    ],
    'testVACreationIfBankAccountDoesNotExist' => [
        'request' => [
            'content' => [
                "type" =>  "refund_credit",
                "method"=> "account_transfer"
            ],
            'method'    => 'POST',
            'url'       => '/fund_addition/initialize',
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_MERCHANT_NO_BANK_ACCOUNT_FOUND
        ],
    ],
    'testCreateOrderForRefundCreditAdditionIfBankAccountDoesNotExist' => [
        'request' => [
            'content' => [
                "type" =>  "refund_credit",
                "method"=> "online_payment",
                "amount"=> 10000
            ],
            'method'    => 'POST',
            'url'       => '/fund_addition/initialize',
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_MERCHANT_NO_BANK_ACCOUNT_FOUND
        ],
    ],
    'testFundAdditionViaWebhookWithNotesNotPresent' => [
        'request' => [
            'content' => [

                "entity" => "event",
                "account_id" => "acc_BFQ7uQEaa7j2z7",
                "event" => "virtual_account.credited",
                "contains" => [
                    "payment",
                    "virtual_account",
                    "bank_transfer"
                ],
                "payload" => [
                    "payment" => [
                        "entity" => [
                            "id" => "pay_DETA2KrOlhqQzF",
                            "entity" => "payment",
                            "amount" => 61900,
                            "currency" => "INR",
                            "status" => "captured",
                            "order_id" => null,
                            "invoice_id" => null,
                            "international" => false,
                            "method" => "bank_transfer",
                            "amount_refunded" => 0,
                            "amount_transferred"=> 0,
                            "refund_status" => null,
                            "captured" => true,
                            "customer_id" => "cust_BtQNqzmBlAXyTY",
                            "notes" => [],
                            "fee" => 731,
                            "tax" => 112,
                            "error_code" => null,
                            "error_description" => null,
                            "created_at" => 1567675983
                        ]
                    ],
                    "virtual_account" => [
                        "entity" => [
                            "id" => "va_DET8z3wBxfPB5L",
                            "name" => "Acme Corp",
                            "entity" => "virtual_account",
                            "status" =>  "active",
                            "description" => "Virtual Account to test webhook",
                            "amount_expected" => null,
                            "notes" => [
                            ],
                            "amount_paid" => 61900,
                            "customer_id" => "cust_BtQNqzmBlAXyTY",

                        ]
                    ],
                    "bank_transfer" => [
                        "entity"=> [
                            "id" => "bt_DETA2KSUJ3uCM9",
                            "entity" => "bank_transfer",
                            "payment_id"=> "pay_DETA2KrOlhqQzF",
                            "mode" => "NEFT",
                            "bank_reference" => "156767598340",
                            "amount" => 61900,
                            "payer_bank_account" => [
                                "id" => "ba_DETA2UuuKtKLR1",
                                "entity" => "bank_account",
                                "ifsc" =>  "KKBK0000007",
                                "bank_name" =>  "Kotak Mahindra Bank",
                                "name" => "Saurav Kumar",
                                "account_number" => "765432123456789"
                            ],
                            "virtual_account_id" => "va_DET8z3wBxfPB5L"
                        ]

                    ],
                    "created_at"=> 1567675983
                ]
            ],
            'method'    => 'POST',
            'url'       => '/fund_addition/account_transfer/webhook',
        ],
        'response'  => [
            'content'     => [
                'error_code' => ErrorCode::BAD_REQUEST_MERCHANT_INFO_NOT_PRESENT_FOR_FUND_ADDITION
            ],
            'status_code' => 200,
        ],
    ],
    'testFundAdditionWebhookWithFundAdditionInCreditsWithFeeMoreThanAmount' => [
        'request'   => [
            'content' => [
                "entity" => "event",
                "account_id" => "acc_BFQ7uQEaa7j2z7",
                "event" => "order.paid",
                "contains" => [
                    "payment",
                    "order"
                ],
                "payload"=> [
                    "payment" => [
                        "entity"=> [
                            "id" => "pay_DESlfW9H8K9uqM",
                            "entity" => "payment",
                            "amount" => 100,
                            "currency" => "INR",
                            "status" => "captured",
                            "order_id" => "order_DESlLckIVRkHWj",
                            "invoice_id" => null,
                            "international" =>  false,
                            "method" => "netbanking",
                            "amount_refunded" => 0,
                            "refund_status"=> null,
                            "captured" => true,
                            "description" => null,
                            "card_id" => null,
                            "bank" => "HDFC",
                            "wallet" =>  null,
                            "vpa" => null,
                            "email" => "gaurav.kumar@example.com",
                            "contact" => "+919876543210",
                            "notes" => [
                                "merchant_id" => "10000000000001",
                                "type" => 'refund_credit'
                            ],
                            "fee" => 110,
                            "tax" =>  0,
                            "error_code" => null,
                            "error_description"=>  null,
                            "created_at" => 1567674599
                        ]
                    ],
                    "order" => [
                        "entity" => [
                            "id" => "order_DESlLckIVRkHWj",
                            "entity" =>  "order",
                            "amount" => 100,
                            "amount_paid" => 100,
                            "amount_due" => 0,
                            "currency" => "INR",
                            "receipt" => "rcptid #1",
                            "offer_id" =>  null,
                            "status" => "paid",
                            "attempts" => 1,
                            "notes" => [
                                "merchant_id" => '10000000000001',
                                'type' => 'refund_credit'
                            ],
                            "created_at" => 1567674581
                        ]
                    ]
                ],
                "created_at"=> 1567674606
            ],
            'method'  => 'POST',
            'url'     => '/fund_addition/online_payment/webhook',
        ],
        'response'  => [
            'content'     => [
                'error_code' => ErrorCode::BAD_REQUEST_INVALID_AMOUNT_FOR_FUND_ADDITION
            ],
            'status_code' => 200,
        ],
    ],
    'testFundAdditionWebhookWithFundAdditionInReserveBalanceWithFeeMoreThanAmount' => [
        'request'   => [
            'content' => [
                "entity" => "event",
                "account_id" => "acc_BFQ7uQEaa7j2z7",
                "event" => "order.paid",
                "contains" => [
                    "payment",
                    "order"
                ],
                "payload"=> [
                    "payment" => [
                        "entity"=> [
                            "id" => "pay_DESlfW9H8K9uqM",
                            "entity" => "payment",
                            "amount" => 100,
                            "currency" => "INR",
                            "status" => "captured",
                            "order_id" => "order_DESlLckIVRkHWj",
                            "invoice_id" => null,
                            "international" =>  false,
                            "method" => "netbanking",
                            "amount_refunded" => 0,
                            "refund_status"=> null,
                            "captured" => true,
                            "description" => null,
                            "card_id" => null,
                            "bank" => "HDFC",
                            "wallet" =>  null,
                            "vpa" => null,
                            "email" => "gaurav.kumar@example.com",
                            "contact" => "+919876543210",
                            "notes" => [
                                "merchant_id" => "10000000000001",
                                "type" => 'refund_credit'
                            ],
                            "fee" => 110,
                            "tax" =>  0,
                            "error_code" => null,
                            "error_description"=>  null,
                            "created_at" => 1567674599
                        ]
                    ],
                    "order" => [
                        "entity" => [
                            "id" => "order_DESlLckIVRkHWj",
                            "entity" =>  "order",
                            "amount" => 100,
                            "amount_paid" => 100,
                            "amount_due" => 0,
                            "currency" => "INR",
                            "receipt" => "rcptid #1",
                            "offer_id" =>  null,
                            "status" => "paid",
                            "attempts" => 1,
                            "notes" => [
                                "merchant_id" => '10000000000001',
                                'type' => 'reserve_balance'
                            ],
                            "created_at" => 1567674581
                        ]
                    ]
                ],
                "created_at"=> 1567674606
            ],
            'method'  => 'POST',
            'url'     => '/fund_addition/online_payment/webhook',
        ],
        'response'  => [
            'content'     => [
                'error_code' => ErrorCode::BAD_REQUEST_INVALID_AMOUNT_FOR_FUND_ADDITION
            ],
            'status_code' => 200,
        ],
    ],
];
