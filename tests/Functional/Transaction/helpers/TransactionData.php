<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testTransactionAfterCapturingPaymentMerchantIndiaDfbPostPaidFlat' => [
        'request' => [
            'content' => [
                'amount'   => 50000,
                'currency' => 'INR',
                'method'   => 'card',
                'convenience_fee_config' => [
                    "rules" => [
                        [
                            "method" => "card",
                            "fee" => [
                                "payee" => "customer",
                                "flat_value" => 200
                            ]
                        ]
                    ]
                ]
            ],
            'method'  => 'POST',
            'url'     => '/orders',
        ],
        'response'  => [
            'content'     => [
                'amount' => 50000,
                'currency' => 'INR',
                'convenience_fee_config' => [
                    "rules" => [
                        [
                            "method" => "card",
                            "fee" => [
                                "payee" => "customer",
                                "flat_value" => 200
                            ]
                        ]
                    ]
                ]
            ],
        ],
    ],
    'txnDataAfterCapturingPaymentDfbPostPaid' => [
        'entity'          => 'transaction',
        'type'            => 'payment',
        'amount'          => 50200,
        'currency'        => 'INR',
        'debit'           => 0,
        'credit'          => 50000,
        'fee'             => 1000,
        'tax'             => 0,
        'gateway_fee'     => 0,
        'api_fee'         => 0,
        'balance'         => 1050000,
        'merchant_id'     => '10000000000000',
        'pricing_rule_id' => null,
        'channel'         => 'axis',
    ],
    'txnDataAfterCapturingPaymentDfbPostPaidPercent' => [
        'entity'          => 'transaction',
        'type'            => 'payment',
        'amount'          => 10120,
        'currency'        => 'INR',
        'debit'           => 0,
        'credit'          => 10000,
        'fee'             => 300,
        'tax'             => 0,
        'gateway_fee'     => 0,
        'api_fee'         => 0,
        'balance'         => 1010000,
        'merchant_id'     => '10000000000000',
        'pricing_rule_id' => null,
        'channel'         => 'axis',
    ],
    'testAddAdjustment' => [
        'request' => [
            'content' => [
                'merchant_id' => '10000000000000',
                'amount'      => 100,
                'description' => 'random desc',
                'currency'    => 'INR',
                'channel'     => 'axis',
                'type'        => 'primary',
            ],
            'url' => '/adjustments',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'amount'      => 100,
                'description' => 'random desc',
                'channel'     => 'axis',
                'currency'    => 'INR',
            ],
        ],
    ],

    'testGetAdjustmentWithTransaction' => [
        'request' => [
            'content' => [
                'merchant_id' => '10000000000000',
                'amount'      => 100,
                'description' => 'random desc',
                'currency'    => 'INR',
                'channel'     => 'axis',
                'type'        => 'primary',
            ],
            'url' => '/adjustments',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'amount'      => 100,
                'description' => 'random desc',
                'channel'     => 'axis',
                'currency'    => 'INR',
            ],
        ],
    ],

    'testFetchTransactionByAdjustmentId' =>  [
        'request' => [
            'url'    => '/transactions',
            'method' => 'get',
            'content' => [],
        ],
        'response' => [
            'content' => []
            ]
        ],

    'testAddNegativeAdjustment' => [
        'request' => [
            'content' => [
                'merchant_id' => '10000000000000',
                'amount'      => -100,
                'description' => 'random desc',
                'currency'    => 'INR',
                'channel'     => 'axis',
                'type'        => 'primary',
            ],
            'url' => '/adjustments',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'amount'      => -100,
                'description' => 'random desc',
                'channel'     => 'axis',
                'currency'    => 'INR',
            ],
        ],
    ],

    'testAddAdjustmentBalanceDoesNotExist' => [
        'request' => [
            'content' => [
                'merchant_id' => '10000000000000',
                'amount'      => 100,
                'description' => 'random desc',
                'currency'    => 'INR',
                'channel'     => 'axis',
                'type'        => 'banking',
            ],
            'url' => '/adjustments',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_BALANCE_DOES_NOT_EXIST,
                ]
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_BALANCE_DOES_NOT_EXIST,
        ],
    ],

    'testAddReverseAdjustment' => [
        'request' => [
            'content' => [
            ],
            'url'    => '/adjustments/reversal',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
            ],
        ],

    ],

    'txnDataAfterAddingAdjustment' => [
        'entity'          => 'transaction',
        'type'            => 'adjustment',
        'amount'          => 100,
        'currency'        => 'INR',
        'debit'           => 0,
        'credit'          => 100,
        'fee'             => 0,
        'tax'             => 0,
        'gateway_fee'     => 0,
        'api_fee'         => 0,
        'gratis'          => false,
        'balance'         => 1000100,
        'merchant_id'     => '10000000000000',
        'pricing_rule_id' => null,
        'channel'         => 'axis',
    ],

    'txnDataAfterAddingAdjWithNoEscrowUpdate' => [
        'entity'          => 'transaction',
        'type'            => 'adjustment',
        'amount'          => 100,
        'currency'        => 'INR',
        'debit'           => 0,
        'credit'          => 100,
        'fee'             => 0,
        'gateway_fee'     => 0,
        'api_fee'         => 0,
        'gratis'          => false,
        'balance'         => 1000100,
        'merchant_id'     => '10000000000000',
        'pricing_rule_id' => null,
        'channel'         => 'axis',
    ],

    'testGetAdjustment' => [
        'request' => [
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                'entity'      => 'adjustment',
                'amount'      => 100,
                'description' => 'random desc',
                'currency'    => 'INR',
            ]
        ]
    ],

    'testFetchPaymentTransactionIndiaMerchant' => [
        'entity'          => 'transaction',
        'type'            => 'payment',
        'amount'          => 500000,
        'currency'        => 'INR',
        'debit'           => 0,
        'credit'          => 488200,
        'fee'             => 11800,
        'tax'             => 1800,
        'gateway_fee'     => 0,
        'api_fee'         => 0,
        'balance'         => 1488200,
        'merchant_id'     => '10000000000000',
        'pricing_rule_id' => null,
        'channel'         => 'axis',
    ],

    'testFetchPaymentTransactionMalaysiaMerchant' => [
        'entity'          => 'transaction',
        'type'            => 'payment',
        'amount'          => 500000,
        'currency'        => 'MYR',
        'debit'           => 0,
        'credit'          => 490000,
        'fee'             => 10000,
        'tax'             => 0,
        'gateway_fee'     => 0,
        'api_fee'         => 0,
        'balance'         => 1490000,
        'merchant_id'     => '10000000000000',
        'pricing_rule_id' => null,
        'channel'         => 'axis',
    ],

    'txnDataAfterCapturingPayment' => [
        'entity'          => 'transaction',
        'type'            => 'payment',
        'amount'          => 50000,
        'currency'        => 'INR',
        'debit'           => 0,
        'credit'          => 49000,
        'fee'             => 1000,
        'tax'             => 0,
        'gateway_fee'     => 0,
        'api_fee'         => 0,
        'balance'         => 1049000,
        'merchant_id'     => '10000000000000',
        'pricing_rule_id' => null,
        'channel'         => 'axis',
    ],

    'txnDataAfterCapturingPaymentMalaysia' => [
        'entity'          => 'transaction',
        'type'            => 'payment',
        'amount'          => 50000,
        'currency'        => 'MYR',
        'debit'           => 0,
        'credit'          => 49000,
        'fee'             => 1000,
        'tax'             => 0,
        'gateway_fee'     => 0,
        'api_fee'         => 0,
        'balance'         => 1049000,
        'merchant_id'     => '10000000000000',
        'pricing_rule_id' => null,
        'channel'         => 'axis',
    ],

    'testTransactionAfterCapturingPaymentForVasMerchant' => [
        'entity'          => 'transaction',
        'type'            => 'payment',
        'amount'          => 50000,
        'currency'        => 'INR',
        'debit'           => 0,
        'credit'          => 0,
        'fee'             => 1476,
        'tax'             => 226,
        'gateway_fee'     => 0,
        'api_fee'         => 0,
        'balance'         => 1000000,
        'merchant_id'     => '10000000000000',
        'pricing_rule_id' => null,
        'channel'         => 'axis',
    ],

    'testTransactionCreateForOldPayment' => [
        'entity'          => 'transaction',
        'type'            => 'payment',
        'amount'          => 1000000,
        'currency'        => 'INR',
        'debit'           => 0,
        'credit'          => 1000000,
        'fee'             => 0,
        'tax'             => 0,
        'gateway_fee'     => 0,
        'api_fee'         => 0,
        'merchant_id'     => '10000000000000',
        'pricing_rule_id' => '1ZeroPricingR1',
        'channel'         => 'axis',
    ],

    'txnDataAfterRefundingPayment' => [
        'entity'          => 'transaction',
        'type'            => 'refund',
        'amount'          => 50000,
        'currency'        => 'INR',
        'debit'           => 50000,
        'credit'          => 0,
        'fee'             => 0,
        'tax'             => 0,
        'gateway_fee'     => 0,
        'api_fee'         => 0,
        'gratis'          => false,
        'balance'         => 999000,
        'merchant_id'     => '10000000000000',
        'pricing_rule_id' => null,
        'channel'         => 'axis',
    ],

    'txnDataAfterRefundingAuthOnlyPayment' => [
        'entity'          => 'transaction',
        'type'            => 'refund',
        'amount'          => 50000,
        'currency'        => 'INR',
        'debit'           => 0,
        'credit'          => 0,
        'fee'             => 0,
        'tax'             => 0,
        'gateway_fee'     => 0,
        'api_fee'         => 0,
        'gratis'          => false,
        'balance'         => 0,
        'merchant_id'     => '10000000000000',
        'pricing_rule_id' => null,
        'channel'         => 'axis',
    ],

    'txnDataAfterDisputingPayment' => [
        'entity'          => 'transaction',
        'type'            => 'adjustment',
        'amount'          => 1000000,
        'currency'        => 'INR',
        'debit'           => 1000000,
        'credit'          => 0,
        'fee'             => 0,
        'gateway_fee'     => 0,
        'api_fee'         => 0,
        'gratis'          => false,
        'balance'         => 976400,
        'merchant_id'     => '10000000000000',
        'pricing_rule_id' => null,
        'channel'         => 'axis',
    ],

    'txnDataAfterDisputingPaymentWithoutDeduct' => [
        'entity'          => 'transaction',
        'type'            => 'payment',
        'amount'          => 1000000,
        'currency'        => 'INR',
        'debit'           => 0,
        'credit'          => 976400,
        'fee'             => 23600,
        'gateway_fee'     => 0,
        'api_fee'         => 0,
        'gratis'          => false,
        'balance'         => 1976400,
        'merchant_id'     => '10000000000000',
        'pricing_rule_id' => null,
        'channel'         => 'axis',
    ],

    'testRefundWithPartialCredits' => [
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Your account does not have enough credits to carry out the refund operation.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_REFUND_NOT_ENOUGH_CREDITS,
        ],
    ],

    'testFetchAuthPaymentTransaction' => [
        'request' => [
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The payment status should be captured for action to be taken',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_STATUS_NOT_CAPTURED,
        ],
    ],

    'testCreateCreditRepayment' => [
        'request' => [
            'content' => [
                'id'            => 'G1SRTbSC6fQOHo',
                'amount'        => 10000,
                'currency'      => 'INR',
                'merchant_id'   => '10000000000000',
            ],
            'url'    => '/credit_repayments/transaction',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                // 'id'            => 'txn_G1T4sGEJmwIj4w',
                'entity'        => 'transaction',
                'entity_id'     => 'repay_G1SRTbSC6fQOHo',
                'type'          => 'credit_repayment',
                'debit'         => 10000,
                'credit'        => 0,
                'amount'        => 10000,
                'currency'      => 'INR',
                'fee'           => 0,
                'tax'           => 0,
                'settled'       => false,
                // 'created_at'    => 1605448528,
                // 'settled_at'    => 1605448528,
                // 'posted_at'     => 1605448528,
            ],
        ],
    ],

    'testCreateCreditRepaymentWithLowBalance' => [
        'request' => [
            'content' => [
                'id'            => 'G1SRTbSC6fQOHo',
                'amount'        => 1000000000,
                'currency'      => 'INR',
                'merchant_id'   => '10000000000000',
            ],
            'url'    => '/credit_repayments/transaction',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Insufficient available balance to create the transaction.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INSUFFICIENT_MERCHANT_BALANCE,
        ],
    ],

    'testCreateCapitalBalanceTransactionNegativeAmount' => [
        'request' => [
            'content' => [
                'id'            => 'G1SRTbSC6fQOHo',
                'amount'        => -1000,
                'currency'      => 'INR',
                'merchant_id'   => '10000000000000',
                'type'          => 'repayment_breakup',
                'balance_id'    => '',
            ],
            'url'    => '/capital_balances/transaction',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                // 'id'            => 'txn_G1T4sGEJmwIj4w',
                'entity'        => 'transaction',
                'entity_id'     => 'G1SRTbSC6fQOHo',
                'type'          => 'repayment_breakup',
                'debit'         => 1000,
                'credit'        => 0,
                'amount'        => 1000,
                'currency'      => 'INR',
                'fee'           => 0,
                'tax'           => 0,
                'settled'       => false,
                // 'created_at'    => 1605448528,
                // 'settled_at'    => 1605448528,
                // 'posted_at'     => 1605448528,
            ],
        ],
    ],

    'testCreateCapitalBalanceTransactionNegativeAmountWithNegativeBalance' => [
        'request' => [
            'content' => [
                'id'            => 'G1SRTbSC6fQOHo',
                'amount'        => -250000,
                'currency'      => 'INR',
                'merchant_id'   => '10000000000000',
                'type'          => 'repayment_breakup',
                'balance_id'    => '',
            ],
            'url'    => '/capital_balances/transaction',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                // 'id'            => 'txn_G1T4sGEJmwIj4w',
                'entity'        => 'transaction',
                'entity_id'     => 'G1SRTbSC6fQOHo',
                'type'          => 'repayment_breakup',
                'debit'         => 250000,
                'credit'        => 0,
                'amount'        => 250000,
                'currency'      => 'INR',
                'fee'           => 0,
                'tax'           => 0,
                'settled'       => false,
                // 'created_at'    => 1605448528,
                // 'settled_at'    => 1605448528,
                // 'posted_at'     => 1605448528,
            ],
        ],
    ],

    'testCreateCapitalBalanceTransactionNegativeAmountWithNegativeBalanceOnInterest' => [
        'request' => [
            'content' => [
                'id'            => 'G1SRTbSC6fQOHo',
                'amount'        => -250000,
                'currency'      => 'INR',
                'merchant_id'   => '10000000000000',
                'type'          => 'repayment_breakup',
                'balance_id'    => '',
            ],
            'url'    => '/capital_balances/transaction',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_INSUFFICIENT_MERCHANT_BALANCE,
                ]
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INSUFFICIENT_MERCHANT_BALANCE,
        ],
    ],

    'testCreateCapitalBalanceTransactionPositiveAmount' => [
        'request' => [
            'content' => [
                'id'            => 'G1SRTbSC6fQOHo',
                'amount'        => 1000,
                'currency'      => 'INR',
                'merchant_id'   => '10000000000000',
                'type'          => 'repayment_breakup',
                'balance_id'    => '',
            ],
            'url'    => '/capital_balances/transaction',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                // 'id'            => 'txn_G1T4sGEJmwIj4w',
                'entity'        => 'transaction',
                'entity_id'     => 'G1SRTbSC6fQOHo',
                'type'          => 'repayment_breakup',
                'debit'         => 0,
                'credit'        => 1000,
                'amount'        => 1000,
                'currency'      => 'INR',
                'fee'           => 0,
                'tax'           => 0,
                'settled'       => false,
                // 'created_at'    => 1605448528,
                // 'settled_at'    => 1605448528,
                // 'posted_at'     => 1605448528,
            ],
        ],
    ],

    'testCreateMultipleCapitalBalanceTransactionPositiveAmount' => [
        'request' => [
            'content' => [
                'repayment_id'          => 'G1SRTbSC6fQOHx',
                'repayment_breakups'    => [
                    [
                        'id'            => 'G1SRTbSC6fQOHo',
                        'amount'        => -1000,
                        'currency'      => 'INR',
                        'merchant_id'   => '10000000000000',
                        'type'          => 'repayment_breakup',
                        'balance_id'    => '',
                    ],
                    [
                        'id'            => 'G1SRTbSC6fQOHp',
                        'amount'        => -9,
                        'currency'      => 'INR',
                        'merchant_id'   => '10000000000000',
                        'type'          => 'repayment_breakup',
                        'balance_id'    => '',
                    ],
                ],
            ],
            'url'    => '/capital_balances/multi_transactions',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testTransactionsBulkUpdateBalanceId' => [
        'request' => [
            'url'    => '/admin/transaction/balance_id_update',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testTransactionsBulkUpdateBalanceIdLimitTest' => [
    ],

    'testPaymentAuthorizedTransactionCreateAfterCaptureTransactionsCreateInternal' => [
        'request' => [
            'content' => [
                'payment' => [
                    'id' =>  "GiahjFtNg85OjA",
                    'amount' =>  50000,
                    'base_amount' => 50000,
                    'currency' => "INR",
                    'status' => "captured",
                    'international' => FALSE,
                    'method' => "upi",
                    'amount_refunded' =>  0,
                    'captured' => TRUE,
                    'description' =>  "random description",
                    'bank' => NULL,
                    'vpa' => NULL,
                    'email' => "a@b.com",
                    'contact' =>  "+919918899029",
                    'notes' =>  [
                        'merchant_order_id' =>  "random order id",
                    ],
                    'fee' =>  0,
                    'tax' =>  0,
                    'created_at' =>  1614864014,
                    'captured_at' =>  1614874014,
                    'merchant_id' => "10000000000000"
                ]
            ],
            'url'    => '/internal/transactions',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'entity_id' => 'pay_GiahjFtNg85OjA',
                'type' => 'payment',
                'debit' =>  0,
                'credit' =>  48524,
                'amount' =>  50000,
                'currency' => "INR",
                'fee' =>  1476,
                'tax' =>  226,
                'settled' =>  FALSE,
                'credit_type' => "default",
                'description' =>  "random description",
                'payment_id' => NULL,
                'settlement_utr' => NULL,
                'order_id' => NULL,
                'order_receipt' => NULL,
                'method' => "upi",
                'dispute_id' => NULL,
            ],
        ],
        'request2' => [
            'content' => [
                'payment' => [
                    'id' =>  "GiahjFtNg85OjA",
                    'amount' =>  50000,
                    'base_amount' => 50000,
                    'currency' => "INR",
                    'status' => "authorized",
                    'international' => FALSE,
                    'method' => "upi",
                    'amount_refunded' =>  0,
                    'captured' => TRUE,
                    'description' =>  "random description",
                    'bank' => NULL,
                    'vpa' => NULL,
                    'email' => "a@b.com",
                    'contact' =>  "+919918899029",
                    'notes' =>  [
                        'merchant_order_id' =>  "random order id",
                    ],
                    'fee' =>  0,
                    'tax' =>  0,
                    'created_at' =>  1614864014,
                    'authorized_at' =>  1614874000,
                    'merchant_id' => "10000000000000"
                ]
            ],
            'url'    => '/internal/transactions',
            'method' => 'POST'
        ],
    ],
    'testPaymentCaptureTransactionsCreateInternalWithPCPAuth' => [
        'request' => [
            'content' => [
                "payment" => [
                    'amount' =>  50000,
                    'base_amount' => 50000,
                    "card"        => [
                        "iin"     => "549777",
                        "last4"   => "0501",
                        "type"    => "CREDIT",
                        "network" => "Visa",
                    ],
                    'created_at' =>  1614864014,
                    "currency" => "INR",
                    "email" => "SCRUBBED_PII(0)",
                    "error_code" => "",
                    "error_description" => "",
                    "id" => "GiahjFtNg85OjA",
                    "merchant_id" => "10000000000000",
                    "method" => "card",
                    "reference_id" => "",
                    'captured_at' =>  1614874014,
                    "source_channel" => "in_person",
                    "status" => "captured",
                    "updated_at" => 0,
                ],
                "transaction_id" => "MyIovLsV65izFA",

            ],
            'url'    => '/internal/transactions',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'entity_id' => 'pay_GiahjFtNg85OjA',
                'type' => 'payment',
                'debit' =>  0,
                'credit' =>  49825,
                'amount' =>  50000,
                'currency' => "INR",
                'fee' =>  175,
                'tax' =>  0,
                'settled' =>  FALSE,
                'credit_type' => "default",
                'payment_id' => NULL,
                'settlement_utr' => NULL,
                'order_id' => NULL,
                'order_receipt' => NULL,
                'method' => "card",
                ]
            ]
    ],
    'testPaymentCaptureTransactionsCreateInternal' => [
        'request' => [
            'content' => [
                'payment' => [
                    'id' =>  "GiahjFtNg85OjA",
                    'amount' =>  50000,
                    'base_amount' => 50000,
                    'currency' => "INR",
                    'status' => "captured",
                    'card' => [
                        'id'                =>  'GiahjFtNg85OjA',
                        'merchant_id'       =>  '10000000000000',
                        'name'              =>  'test',
                        'network'           =>  'RuPay',
                        'expiry_month'      =>  '12',
                        'expiry_year'       =>  '2100',
                        'issuer'            =>  'hdfc',
                        'type'              =>  'debit',
                        'iin'               =>  '607384',
                        'last4'             =>  '1111',
                        'vault_token'       =>  'NjA3Mzg0OTcwMDAwNDk0Nw==',
                        'vault'             =>  'rzpvault',
                    ],
                    'international' => FALSE,
                    'method' => "card",
                    'amount_refunded' =>  0,
                    'captured' => TRUE,
                    'description' =>  "random description",
                    'bank' => NULL,
                    'wallet' => NULL,
                    'vpa' => NULL,
                    'email' => "a@b.com",
                    'contact' =>  "+919918899029",
                    'notes' =>  [
                        'merchant_order_id' =>  "random order id",
                    ],
                    'fee' =>  0,
                    'tax' =>  0,
                    'created_at' =>  1614864014,
                    'captured_at' =>  1614874014,
                    'merchant_id' => "10000000000000"
                ]
            ],
            'url'    => '/internal/transactions',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'entity_id' => 'pay_GiahjFtNg85OjA',
                'type' => 'payment',
                'debit' =>  0,
                'credit' =>  49000,
                'amount' =>  50000,
                'currency' => "INR",
                'fee' =>  1000,
                'tax' =>  0,
                'settled' =>  FALSE,
                'credit_type' => "default",
                'description' =>  "random description",
                'payment_id' => NULL,
                'settlement_utr' => NULL,
                'order_id' => NULL,
                'order_receipt' => NULL,
                'method' => "card",
                'card_network' => "RuPay",
                'card_issuer' => "hdfc",
                'card_type' => "debit",
                'dispute_id' => NULL,
            ],
        ],
    ],

    'testHDFCSurchargeNonDsPaymentCaptureTransactionsCreateInternal' => [
        'request' => [
            'content' => [
                'payment' => [
                    'id' =>  "GiahjFtNg85OjA",
                    'amount' =>  50000,
                    'base_amount' => 50000,
                    'currency' => "INR",
                    'status' => "captured",
                    'gateway' => 'hdfc',
                    'card' => [
                        'id'                =>  'GiahjFtNg85OjA',
                        'merchant_id'       =>  '10000000000000',
                        'name'              =>  'test',
                        'network'           =>  'RuPay',
                        'expiry_month'      =>  '12',
                        'expiry_year'       =>  '2100',
                        'issuer'            =>  'hdfc',
                        'type'              =>  'debit',
                        'iin'               =>  '607384',
                        'last4'             =>  '1111',
                        'vault_token'       =>  'NjA3Mzg0OTcwMDAwNDk0Nw==',
                        'vault'             =>  'rzpvault',
                    ],
                    'international' => FALSE,
                    'method' => "card",
                    'amount_refunded' =>  0,
                    'captured' => TRUE,
                    'description' =>  "random description",
                    'bank' => NULL,
                    'wallet' => NULL,
                    'vpa' => NULL,
                    'email' => "a@b.com",
                    'contact' =>  "+919918899029",
                    'notes' =>  [
                        'merchant_order_id' =>  "random order id",
                    ],
                    'fee' =>  0,
                    'tax' =>  0,
                    'created_at' =>  1614864014,
                    'captured_at' =>  1614874014,
                    'merchant_id' => "10000000000000"
                ]
            ],
            'url'    => '/internal/transactions',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'entity_id' => 'pay_GiahjFtNg85OjA',
                'type' => 'payment',
                'debit' =>  0,
                'credit' =>  50000,
                'amount' =>  50000,
                'currency' => "INR",
                'fee' =>  0,
                'tax' =>  0,
                'settled' =>  FALSE,
                'credit_type' => "default",
                'description' =>  "random description",
                'payment_id' => NULL,
                'settlement_utr' => NULL,
                'order_id' => NULL,
                'order_receipt' => NULL,
                'method' => "card",
                'card_network' => "RuPay",
                'card_issuer' => "hdfc",
                'card_type' => "debit",
                'dispute_id' => NULL,
            ],
        ],
    ],

    'testTransactionCreatedWebhookForSuccessfulMappingToPayout' => [
        'entity'   => 'event',
        'event'    => 'transaction.created',
        'contains' => [
            'transaction',
        ],
        'payload'  => [
            'transaction' => [
                'entity' => [
                    'entity' => 'transaction',
                    'source'   => [
                        'entity' => 'payout',
                        'status' => 'processed',
                    ],
                ],
            ],
        ],
    ],

    'testTransactionCreatedWebhookForSuccessfulMappingToReversal' => [
        'entity'   => 'event',
        'event'    => 'transaction.created',
        'contains' => [
            'transaction',
        ],
        'payload'  => [
            'transaction' => [
                'entity' => [
                    'entity' => 'transaction',
                    'source'   => [
                        'entity' => 'reversal',
                    ],
                ],
            ],
        ],
    ],

    'testTransactionCreatedWebhookForSuccessfulMappingToExternal' => [
        'entity'   => 'event',
        'event'    => 'transaction.created',
        'contains' => [
            'transaction',
        ],
        'payload'  => [
            'transaction' => [
                'entity' => [
                    'entity' => 'transaction',
                    'source'   => [
                        'entity' => 'external',
                    ],
                ],
            ],
        ],
    ],

    'testFindPayoutTransactionFromLedger' => [
        'payload' => [
            'id'               => 'M4S5z25LS4XGgN',
            'created_at'       => '1634027277',
            'updated_at'       => '1634027277',
            'amount'           => '354.000000',
            'base_amount'      => '354.000000',
            'currency'         => 'INR',
            'tenant'           => 'X',
            'transactor_id'    => 'pout_samplePoutId51',
            'transactor_event' => 'payout_initiated',
            'transaction_date' => '1611132045',
            'ledger_entry'     => [
                [
                    'id'               => 'I8MJlgVttAs4KQ',
                    'created_at'       => '1634027277',
                    'updated_at'       => '1634027277',
                    'merchant_id'      => '10000000000000',
                    'journal_id'       => 'M4S5z25LS4XGgN',
                    'account_id'       => 'GoRNyEuu9Hl0OZ',
                    'amount'           => '300.000000',
                    'base_amount'      => '300.000000',
                    'type'             => 'credit',
                    'currency'         => 'INR',
                    'balance'          => '98410.000000',
                    'balance_updated'  => true,
                    'account_entities' => [
                        'account_type'       => ['payable',],
                        'banking_account_id' => ['bacc_JLcwWU3SsZ7byJ',],
                        'fund_account_type'  => ['merchant_va_vendor',],
                        'transactor'         => ['X',],
                    ],
                ],
                [
                    'id'               => 'I8MJlgVttAs4KR',
                    'created_at'       => '1634027277',
                    'updated_at'       => '1634027277',
                    'merchant_id'      => '10000000000000',
                    'journal_id'       => 'M4S5z25LS4XGgN',
                    'account_id'       => 'IwwUK4yc0R4avZ',
                    'amount'           => '354.000000',
                    'base_amount'      => '354.000000',
                    'type'             => 'debit',
                    'currency'         => 'INR',
                    'balance'          => '98410.000000',
                    'balance_updated'  => true,
                    'account_entities' => [
                        'account_type'       => ['payable',],
                        'banking_account_id' => ['bacc_JLcwWU3SsZ7byJ',],
                        'fund_account_type'  => ['merchant_va',],
                        'transactor'         => ['X',],
                    ],
                ],
                [
                    'id'               => 'K0Rf8eRaOhG5nN',
                    'created_at'       => '1634027277',
                    'updated_at'       => '1634027277',
                    'merchant_id'      => '10000000000000',
                    'journal_id'       => 'M4S5z25LS4XGgN',
                    'account_id'       => 'IwwUK6KYrdHY2m',
                    'amount'           => '4.000000',
                    'base_amount'      => '4.000000',
                    'type'             => 'credit',
                    'currency'         => 'INR',
                    'balance'          => '88236.000000',
                    'balance_updated'  => true,
                    'account_entities' => [
                        'account_type'       => ['payable',],
                        'banking_account_id' => ['bacc_JLcwWU3SsZ7byJ',],
                        'fund_account_type'  => ['va_gst',],
                        'transactor'         => ['X',],
                    ],
                ],
                [
                    'id'               => 'K0Rf8eRaOhG5nN',
                    'created_at'       => '1634027277',
                    'updated_at'       => '1634027277',
                    'merchant_id'      => '10000000000000',
                    'journal_id'       => 'M4S5z25LS4XGgN',
                    'account_id'       => 'IwwUK6KYrdHY2m',
                    'amount'           => '50.000000',
                    'base_amount'      => '50.000000',
                    'type'             => 'credit',
                    'currency'         => 'INR',
                    'balance'          => '88236.000000',
                    'balance_updated'  => true,
                    'account_entities' => [
                        'account_type'       => ['cash',],
                        'banking_account_id' => ['bacc_JLcwWU3SsZ7byJ',],
                        'fund_account_type'  => ['merchant_va',],
                        'transactor'         => ['X',],
                    ],
                ],
            ],
        ],
    ],

    'testFindPayoutTransactionFromLedgerExternalFetchDisabled' => [
        'payload' => [
            'id'                => 'M4S5z25LS4XGgN',
            'entity_id'         => 'samplePoutId51',
            'merchant_id'       => '10000000000000',
            'amount'            => '354.000000',
            'currency'          => 'INR',
            'credit'            => '0.000000',
            'debit'             => '354.000000',
            'balance'           => '98410.000000',
            'created_at'        => '1634027277',
            'type'              => 'payout',
            'fee'               => '54.000000',
            'tax'               => '4.000000',
            'channel'           => 'yesbank',
            'credits'           => '0',
            'credit_type'       => 'default',
            'balance_id'        => 'sampleBalanceId51',
            'updated_at'        => '1634027277',
        ],
    ],

    'testFindPayoutTransactionFromLedgerExternalFetchEnabledReturnDisabled' => [
        'apiPayload' => [
            'id'                => 'M4S5z25LS4XGgN',
            'entity_id'         => 'samplePoutId51',
            'merchant_id'       => '10000000000000',
            'amount'            => '354.000000',
            'currency'          => 'INR',
            'credit'            => '0.000000',
            'debit'             => '354.000000',
            'balance'           => '98410.000000',
            'created_at'        => '1634027277',
            'type'              => 'payout',
            'fee'               => '54.000000',
            'tax'               => '4.000000',
            'channel'           => 'yesbank',
            'credits'           => '0',
            'credit_type'       => 'default',
            'balance_id'        => 'sampleBalanceId51',
            'updated_at'        => '1634027277',
        ],
        'ledgerPayload' => [
            'id'               => 'M4S5z25LS4XGgN',
            'created_at'       => '1634027277',
            'updated_at'       => '1634027277',
            'amount'           => '354.000000',
            'base_amount'      => '354.000000',
            'currency'         => 'INR',
            'tenant'           => 'X',
            'transactor_id'    => 'pout_samplePoutId51',
            'transactor_event' => 'payout_initiated',
            'transaction_date' => '1611132045',
            'ledger_entry'     => [
                [
                    'id'               => 'I8MJlgVttAs4KQ',
                    'created_at'       => '1634027277',
                    'updated_at'       => '1634027277',
                    'merchant_id'      => '10000000000000',
                    'journal_id'       => 'M4S5z25LS4XGgN',
                    'account_id'       => 'GoRNyEuu9Hl0OZ',
                    'amount'           => '300.000000',
                    'base_amount'      => '300.000000',
                    'type'             => 'credit',
                    'currency'         => 'INR',
                    'balance'          => '98410.000000',
                    'balance_updated'  => true,
                    'account_entities' => [
                        'account_type'       => ['payable',],
                        'banking_account_id' => ['bacc_JLcwWU3SsZ7byJ',],
                        'fund_account_type'  => ['merchant_va_vendor',],
                        'transactor'         => ['X',],
                    ],
                ],
                [
                    'id'               => 'I8MJlgVttAs4KR',
                    'created_at'       => '1634027277',
                    'updated_at'       => '1634027277',
                    'merchant_id'      => '10000000000000',
                    'journal_id'       => 'M4S5z25LS4XGgN',
                    'account_id'       => 'IwwUK4yc0R4avZ',
                    'amount'           => '354.000000',
                    'base_amount'      => '354.000000',
                    'type'             => 'debit',
                    'currency'         => 'INR',
                    'balance'          => '98410.000000',
                    'balance_updated'  => true,
                    'account_entities' => [
                        'account_type'       => ['payable',],
                        'banking_account_id' => ['bacc_JLcwWU3SsZ7byJ',],
                        'fund_account_type'  => ['merchant_va',],
                        'transactor'         => ['X',],
                    ],
                ],
                [
                    'id'               => 'K0Rf8eRaOhG5nN',
                    'created_at'       => '1634027277',
                    'updated_at'       => '1634027277',
                    'merchant_id'      => '10000000000000',
                    'journal_id'       => 'M4S5z25LS4XGgN',
                    'account_id'       => 'IwwUK6KYrdHY2m',
                    'amount'           => '4.000000',
                    'base_amount'      => '4.000000',
                    'type'             => 'credit',
                    'currency'         => 'INR',
                    'balance'          => '88236.000000',
                    'balance_updated'  => true,
                    'account_entities' => [
                        'account_type'       => ['payable',],
                        'banking_account_id' => ['bacc_JLcwWU3SsZ7byJ',],
                        'fund_account_type'  => ['va_gst',],
                        'transactor'         => ['X',],
                    ],
                ],
                [
                    'id'               => 'K0Rf8eRaOhG5nN',
                    'created_at'       => '1634027277',
                    'updated_at'       => '1634027277',
                    'merchant_id'      => '10000000000000',
                    'journal_id'       => 'M4S5z25LS4XGgN',
                    'account_id'       => 'IwwUK6KYrdHY2m',
                    'amount'           => '50.000000',
                    'base_amount'      => '50.000000',
                    'type'             => 'credit',
                    'currency'         => 'INR',
                    'balance'          => '88236.000000',
                    'balance_updated'  => true,
                    'account_entities' => [
                        'account_type'       => ['cash',],
                        'banking_account_id' => ['bacc_JLcwWU3SsZ7byJ',],
                        'fund_account_type'  => ['merchant_va',],
                        'transactor'         => ['X',],
                    ],
                ],
            ],
        ],
    ],

    'testFindReversalTransactionFromLedger' => [
        'payload' => [
            'id'               => 'M4S5z25LS4XGgN',
            'created_at'       => '1634027277',
            'updated_at'       => '1634027277',
            'amount'           => '354.000000',
            'base_amount'      => '354.000000',
            'currency'         => 'INR',
            'tenant'           => 'X',
            'transactor_id'    => 'rvrsl_sampleRvslId51',
            'transactor_event' => 'payout_failed',
            'transaction_date' => '1611132045',
            'ledger_entry'     => [
                [
                    'id'               => 'I8MJlgVttAs4KQ',
                    'created_at'       => '1634027277',
                    'updated_at'       => '1634027277',
                    'merchant_id'      => '10000000000000',
                    'journal_id'       => 'M4S5z25LS4XGgN',
                    'account_id'       => 'GoRNyEuu9Hl0OZ',
                    'amount'           => '300.000000',
                    'base_amount'      => '300.000000',
                    'type'             => 'debit',
                    'currency'         => 'INR',
                    'balance'          => '98410.000000',
                    'balance_updated'  => true,
                    'account_entities' => [
                        'account_type'       => ['payable',],
                        'banking_account_id' => ['bacc_JLcwWU3SsZ7byJ',],
                        'fund_account_type'  => ['merchant_va_vendor',],
                        'transactor'         => ['X',],
                    ],
                ],
                [
                    'id'               => 'I8MJlgVttAs4KR',
                    'created_at'       => '1634027277',
                    'updated_at'       => '1634027277',
                    'merchant_id'      => '10000000000000',
                    'journal_id'       => 'M4S5z25LS4XGgN',
                    'account_id'       => 'IwwUK4yc0R4avZ',
                    'amount'           => '354.000000',
                    'base_amount'      => '354.000000',
                    'type'             => 'credit',
                    'currency'         => 'INR',
                    'balance'          => '98410.000000',
                    'balance_updated'  => true,
                    'account_entities' => [
                        'account_type'       => ['payable',],
                        'banking_account_id' => ['bacc_JLcwWU3SsZ7byJ',],
                        'fund_account_type'  => ['merchant_va',],
                        'transactor'         => ['X',],
                    ],
                ],
                [
                    'id'               => 'K0Rf8eRaOhG5nN',
                    'created_at'       => '1634027277',
                    'updated_at'       => '1634027277',
                    'merchant_id'      => '10000000000000',
                    'journal_id'       => 'M4S5z25LS4XGgN',
                    'account_id'       => 'IwwUK6KYrdHY2m',
                    'amount'           => '4.000000',
                    'base_amount'      => '4.000000',
                    'type'             => 'credit',
                    'currency'         => 'INR',
                    'balance'          => '88236.000000',
                    'balance_updated'  => true,
                    'account_entities' => [
                        'account_type'       => ['payable',],
                        'banking_account_id' => ['bacc_JLcwWU3SsZ7byJ',],
                        'fund_account_type'  => ['va_gst',],
                        'transactor'         => ['X',],
                    ],
                ],
                [
                    'id'               => 'K0Rf8eRaOhG5nN',
                    'created_at'       => '1634027277',
                    'updated_at'       => '1634027277',
                    'merchant_id'      => '10000000000000',
                    'journal_id'       => 'M4S5z25LS4XGgN',
                    'account_id'       => 'IwwUK6KYrdHY2m',
                    'amount'           => '50.000000',
                    'base_amount'      => '50.000000',
                    'type'             => 'credit',
                    'currency'         => 'INR',
                    'balance'          => '88236.000000',
                    'balance_updated'  => true,
                    'account_entities' => [
                        'account_type'       => ['cash',],
                        'banking_account_id' => ['bacc_JLcwWU3SsZ7byJ',],
                        'fund_account_type'  => ['merchant_va',],
                        'transactor'         => ['X',],
                    ],
                ],
            ],
        ],
    ],

    'testFindFAVTransactionFromLedger' => [
        'payload' => [
            'id'               => 'M4S5z25LS4XGgN',
            'created_at'       => '1634027277',
            'updated_at'       => '1634027277',
            'amount'           => '354.000000',
            'base_amount'      => '354.000000',
            'currency'         => 'INR',
            'tenant'           => 'X',
            'transactor_id'    => 'fav_sampleFavId511',
            'transactor_event' => 'fav_initiated',
            'transaction_date' => '1611132045',
            'ledger_entry'     => [
                [
                    'id'               => 'I8MJlgVttAs4KR',
                    'created_at'       => '1634027277',
                    'updated_at'       => '1634027277',
                    'merchant_id'      => '10000000000000',
                    'journal_id'       => 'M4S5z25LS4XGgN',
                    'account_id'       => 'IwwUK4yc0R4avZ',
                    'amount'           => '354.000000',
                    'base_amount'      => '354.000000',
                    'type'             => 'debit',
                    'currency'         => 'INR',
                    'balance'          => '98410.000000',
                    'balance_updated'  => true,
                    'account_entities' => [
                        'account_type'       => ['payable',],
                        'banking_account_id' => ['bacc_JLcwWU3SsZ7byJ',],
                        'fund_account_type'  => ['merchant_va',],
                        'transactor'         => ['X',],
                    ],
                ],
                [
                    'id'               => 'K0Rf8eRaOhG5nN',
                    'created_at'       => '1634027277',
                    'updated_at'       => '1634027277',
                    'merchant_id'      => '10000000000000',
                    'journal_id'       => 'M4S5z25LS4XGgN',
                    'account_id'       => 'IwwUK6KYrdHY2m',
                    'amount'           => '4.000000',
                    'base_amount'      => '4.000000',
                    'type'             => 'credit',
                    'currency'         => 'INR',
                    'balance'          => '88236.000000',
                    'balance_updated'  => true,
                    'account_entities' => [
                        'account_type'       => ['payable',],
                        'banking_account_id' => ['bacc_JLcwWU3SsZ7byJ',],
                        'fund_account_type'  => ['va_gst',],
                        'transactor'         => ['X',],
                    ],
                ],
                [
                    'id'               => 'K0Rf8eRaOhG5nN',
                    'created_at'       => '1634027277',
                    'updated_at'       => '1634027277',
                    'merchant_id'      => '10000000000000',
                    'journal_id'       => 'M4S5z25LS4XGgN',
                    'account_id'       => 'IwwUK6KYrdHY2m',
                    'amount'           => '50.000000',
                    'base_amount'      => '50.000000',
                    'type'             => 'credit',
                    'currency'         => 'INR',
                    'balance'          => '88236.000000',
                    'balance_updated'  => true,
                    'account_entities' => [
                        'account_type'       => ['cash',],
                        'banking_account_id' => ['bacc_JLcwWU3SsZ7byJ',],
                        'fund_account_type'  => ['merchant_va',],
                        'transactor'         => ['X',],
                    ],
                ],
            ],
        ],
    ],

    'testFindAdjustmentTransactionFromLedger' => [
        'payload' => [
            'id'               => 'M4S5z25LS4XGgN',
            'created_at'       => '1634027277',
            'updated_at'       => '1634027277',
            'amount'           => '500.000000',
            'base_amount'      => '354.000000',
            'currency'         => 'INR',
            'tenant'           => 'X',
            'transactor_id'    => 'adj_sampleAdjId511',
            'transactor_event' => 'negative_adjustment_processed',
            'transaction_date' => '1611132045',
            'ledger_entry'     => [
                [
                    'id'               => 'I8MJlgVttAs4KR',
                    'created_at'       => '1634027277',
                    'updated_at'       => '1634027277',
                    'merchant_id'      => '10000000000000',
                    'journal_id'       => 'M4S5z25LS4XGgN',
                    'account_id'       => 'IwwUK4yc0R4avZ',
                    'amount'           => '500.000000',
                    'base_amount'      => '500.000000',
                    'type'             => 'debit',
                    'currency'         => 'INR',
                    'balance'          => '98410.000000',
                    'balance_updated'  => true,
                    'account_entities' => [
                        'account_type'       => ['payable',],
                        'banking_account_id' => ['bacc_JLcwWU3SsZ7byJ',],
                        'fund_account_type'  => ['merchant_va',],
                        'transactor'         => ['X',],
                    ],
                ],
                [
                    'id'               => 'K0Rf8eRaOhG5nN',
                    'created_at'       => '1634027277',
                    'updated_at'       => '1634027277',
                    'merchant_id'      => '10000000000000',
                    'journal_id'       => 'M4S5z25LS4XGgN',
                    'account_id'       => 'IwwUK6KYrdHY2m',
                    'amount'           => '500.000000',
                    'base_amount'      => '500.000000',
                    'type'             => 'credit',
                    'currency'         => 'INR',
                    'balance'          => '88236.000000',
                    'balance_updated'  => true,
                    'account_entities' => [
                        'account_type'       => ['payable',],
                        'fund_account_type'  => ['adjustment',],
                        'transactor'         => ['X',],
                    ],
                ],
            ],
        ],
    ],

    'testFindBankTransferTransactionFromLedger' => [
        'payload' => [
            'id'               => 'M4S5z25LS4XGgN',
            'created_at'       => '1634027277',
            'updated_at'       => '1634027277',
            'amount'           => '500.000000',
            'base_amount'      => '354.000000',
            'currency'         => 'INR',
            'tenant'           => 'X',
            'transactor_id'    => 'bt_sampleBnkTrId5',
            'transactor_event' => 'negative_adjustment_processed',
            'transaction_date' => '1611132045',
            'ledger_entry'     => [
                [
                    'id'               => 'I8MJlgVttAs4KR',
                    'created_at'       => '1634027277',
                    'updated_at'       => '1634027277',
                    'merchant_id'      => '10000000000000',
                    'journal_id'       => 'M4S5z25LS4XGgN',
                    'account_id'       => 'IwwUK4yc0R4avZ',
                    'amount'           => '500.000000',
                    'base_amount'      => '500.000000',
                    'type'             => 'credit',
                    'currency'         => 'INR',
                    'balance'          => '98410.000000',
                    'balance_updated'  => true,
                    'account_entities' => [
                        'account_type'       => ['payable',],
                        'banking_account_id' => ['bacc_JLcwWU3SsZ7byJ',],
                        'fund_account_type'  => ['merchant_va',],
                        'transactor'         => ['X',],
                    ],
                ],
                [
                    'id'               => 'K0Rf8eRaOhG5nN',
                    'created_at'       => '1634027277',
                    'updated_at'       => '1634027277',
                    'merchant_id'      => '10000000000000',
                    'journal_id'       => 'M4S5z25LS4XGgN',
                    'account_id'       => 'IwwUK6KYrdHY2m',
                    'amount'           => '500.000000',
                    'base_amount'      => '500.000000',
                    'type'             => 'debit',
                    'currency'         => 'INR',
                    'balance'          => '88236.000000',
                    'balance_updated'  => true,
                    'account_entities' => [
                        'account_type'       => ['receivable',],
                        'fund_account_type'  => ['nodal',],
                        'transactor'         => ['X',],
                    ],
                ],
            ],
        ],
    ],

    'testFindCreditTransferTransactionFromLedger' => [
        'payload' => [
            'id'               => 'M4S5z25LS4XGgN',
            'created_at'       => '1634027277',
            'updated_at'       => '1634027277',
            'amount'           => '500.000000',
            'base_amount'      => '354.000000',
            'currency'         => 'INR',
            'tenant'           => 'X',
            'transactor_id'    => 'ct_sampleCtrId511',
            'transactor_event' => 'va_to_va_credit_processed',
            'transaction_date' => '1611132045',
            'ledger_entry'     => [
                [
                    'id'               => 'I8MJlgVttAs4KR',
                    'created_at'       => '1634027277',
                    'updated_at'       => '1634027277',
                    'merchant_id'      => '10000000000000',
                    'journal_id'       => 'M4S5z25LS4XGgN',
                    'account_id'       => 'IwwUK4yc0R4avZ',
                    'amount'           => '500.000000',
                    'base_amount'      => '500.000000',
                    'type'             => 'credit',
                    'currency'         => 'INR',
                    'balance'          => '98410.000000',
                    'balance_updated'  => true,
                    'account_entities' => [
                        'account_type'       => ['payable',],
                        'banking_account_id' => ['bacc_JLcwWU3SsZ7byJ',],
                        'fund_account_type'  => ['merchant_va',],
                        'transactor'         => ['X',],
                    ],
                ],
                [
                    'id'               => 'K0Rf8eRaOhG5nN',
                    'created_at'       => '1634027277',
                    'updated_at'       => '1634027277',
                    'merchant_id'      => '10000000000000',
                    'journal_id'       => 'M4S5z25LS4XGgN',
                    'account_id'       => 'IwwUK6KYrdHY2m',
                    'amount'           => '500.000000',
                    'base_amount'      => '500.000000',
                    'type'             => 'debit',
                    'currency'         => 'INR',
                    'balance'          => '88236.000000',
                    'balance_updated'  => true,
                    'account_entities' => [
                        'account_type'       => ['payable',],
                        'fund_account_type'  => ['adjustment',],
                        'transactor'         => ['X',],
                    ],
                ],
            ],
        ],
    ],
];
