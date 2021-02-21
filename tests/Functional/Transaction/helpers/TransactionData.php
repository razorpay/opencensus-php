<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
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
];
