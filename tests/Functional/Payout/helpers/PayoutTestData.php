<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;
use RZP\Models\Payout\Status as PayoutStatus;
use RZP\Models\FundTransfer\Attempt\Status as FundTransferAttemptStatus;

return [
    'testCreatePayout' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'fund_account_id' => 'fa_100000000000fa',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'narration'       => 'Batman',
                'purpose'         => 'refund',
                'status'          => 'processing',
                'tax'             => 162,
                'fees'            => 1062,
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
    ],

    'testCreatePayoutWithOtp' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'fund_account_id' => 'fa_100000000000fa',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'purpose'         => 'refund',
                'tax'             => 162,
                'fees'            => 1062,
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
    ],

    'testApprovePayoutWithOtp' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts/approve/{id}',
            'content' => [
                'token' => 'BUIj3m2Nx2VvVj',
                'otp'   => '0007',
            ],
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testApprovePayoutWithInvalidOtp' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts/approve/{id}',
            'content' => [
                'token' => 'BUIj3m2Nx2VvVj',
                'otp'   => '1234',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_INCORRECT_OTP,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INCORRECT_OTP,
        ],
    ],

    'testApproveBulkPayoutWithOtp' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts/approve',
            'content' => [
                'payout_ids' => [],
                'token'      => 'BUIj3m2Nx2VvVj',
                'otp'        => '0007',
            ],
        ],
        'response' => [
            'content' => [
                'total_count' => 2,
                'failed_ids'  => [],
            ],
        ],
    ],

    'testRejectPayout' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts/reject/{id}',
            'content' => [
                'token' => 'BUIj3m2Nx2VvVj',
                'otp'   => '1234',
            ],
        ],
        'response' => [
            'content' => [
                'status' => 'pending',
            ],
        ],
    ],

    'testBulkRejectPayouts' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts/reject',
            'content' => [
                'payout_ids' => [],
            ],
        ],
        'response' => [
            'content' => [
                'total_count' => 2,
                'failed_ids'  => [],
            ],
        ],
    ],

    'testCreatePayoutForAmountLessThanMinFee' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 100,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'fund_account_id' => 'fa_100000000000fa',
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 100,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'purpose'         => 'refund',
                'tax'             => 90,
                'fees'            => 590,
                'notes'           => [],
            ],
        ],
    ],

    'testCreateQueuedPayout' => [
        'request' => [
            'method'    => 'POST',
            'url'       => '/payouts',
            'content'   => [
                'account_number'        => '2224440041626905',
                'amount'                => 10000001,
                'currency'              => 'INR',
                'purpose'               => 'refund',
                'fund_account_id'       => 'fa_100000000000fa',
                'queue_if_low_balance'  => true,
            ]
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 10000001,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'purpose'         => 'refund',
                'status'          => 'queued',
                'utr'             => null,
                'mode'            => null,
                'tax'             => 0,
                'fees'            => 0,
                'notes'           => [],
            ],
        ],
    ],

    'testCreatePayoutToCardFundAccount' => [
        'request' => [
            'method'    => 'POST',
            'url'       => '/payouts',
            'content'   => [
                'account_number'        => '2224440041626905',
                'amount'                => 100,
                'currency'              => 'INR',
                'purpose'               => 'refund',
                'fund_account_id'       => 'fa_100000000002fa',
                'mode'                  => 'IMPS'
            ]
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 100,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000002fa',
                'purpose'         => 'refund',
                'tax'             => 90,
                'fees'            => 590,
                'notes'           => [],
            ],
        ],
    ],

    'testCreatePayoutToInactiveFundAccount' => [
        'request'   => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 1000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'fund_account_id' => 'fa_100000000001fa',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Payouts cannot be created on an inactive fund account',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreatePayoutToInactiveContactFundAccount' => [
        'request'   => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 1000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'fund_account_id' => 'fa_100000000001fa',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Payouts cannot be created on an inactive contact fund account',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateMerchantPayout' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/merchant/payout',
            'content' => [
                'amount'         => 1000,
                'merchant_id'    => '10000000000000',
            ],
        ],
        'response' => [
            'content' => [
                'entity'      => 'payout',
                'amount'      => 1000,
                'currency'    => 'INR',
                'tax'         => 92,
                'fees'        => 602,
                'notes'       => []
            ],
        ],
    ],

    'testCreateMerchantPayoutWithModulo' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/merchant/payout',
            'content' => [
                'amount'         => 1200,
                'merchant_id'    => '10000000000000',
                'modulo'         => 1000
            ],
        ],
        'response' => [
            'content' => [
                'entity'      => 'payout',
                'amount'      => 1000,
                'currency'    => 'INR',
                'tax'         => 92,
                'fees'        => 602,
                'notes'       => []
            ],
        ],
    ],

    'testCreateMerchantPayoutWithMinAmount' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/merchant/payout',
            'content' => [
                'amount'         => 2200,
                'merchant_id'    => '10000000000000',
                'min_amount'     => 3000,
                'modulo'         => 1000
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'amount is less than min amount',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreatePayoutFundsOnHold' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'    => '2224440041626905',
                'amount'            => 1000000,
                'currency'          => 'INR',
                'destination'       => 'ba_9LfZofLRJIpwrH',
                'customer_id'       => 'cust_100000customer',
                'notes'             => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_MERCHANT_FUNDS_ON_HOLD,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_MERCHANT_FUNDS_ON_HOLD,
        ],
    ],


    'testCreatePayoutInsufficientBalance' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'account_number'    => '2224440041626905',
                'amount'            => 300000000,
                'currency'          => 'INR',
                'fund_account_id'   => 'fa_100000000000fa',
                'purpose'           => 'refund',
                'notes'             => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PAYOUT_NOT_ENOUGH_BALANCE_BANKING,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYOUT_NOT_ENOUGH_BALANCE_BANKING,
        ],
    ],

    'testGetPayouts' => [
        'request' => [
            'method'  => 'get',
            'url'     => '/payouts?account_number=2224440041626905',
            'content' => [],
        ],
        'response' => [
            'content' => [
            ]
        ]
    ],

    'testGetPayoutsWithoutAccountNumber' => [
        'request' => [
            'method'  => 'get',
            'url'     => '/payouts',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The account number field is required.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testGetPayout' => [
        'request' => [
            'method'  => 'get',
            'url'     => '/payouts/{id}',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
            ]
        ]
    ],

    'testCreatePaymentPayout' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/payments/{id}/payout',
            'content' => [
                'amount'          => 1000,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'purpose'         => 'refund',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'      => 'payout',
                'amount'      => 1000,
                'currency'    => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'tax'         => 92,
                'fees'        => 602,
                'notes'       => [
                    'abc' => 'xyz',
                ],
            ],
        ]
    ],

    'testPaymentPayoutAmountGreaterThanCapture' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/payments/{id}/payout',
            'content' => [
                'amount'      => 3000,
                'currency'    => 'INR',
                'customer_id' => 'cust_100000customer',
                'destination' => 'ba_1000000lcustba',
                'notes'       => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PAYMENT_PAYOUT_AMOUNT_GREATER_THAN_CAPTURED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_PAYOUT_AMOUNT_GREATER_THAN_CAPTURED,
        ],
    ],

    'testPaymentPayoutPartial' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payments/{id}/payout',
            'content' => [
                'amount'          => 2000,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'purpose'         => 'refund',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 2000,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'tax'             => 94,
                'fees'            => 614,
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ]
    ],

    'testCreatePaymentPayoutNotSettledLiveMode' => [
        'request'   => [
            'method'  => 'POST',
            'url'     => '/payments/{id}/payout',
            'content' => [
                'amount'          => 1000,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'purpose'         => 'refund',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PAYMENT_PAYOUT_BEFORE_SETTLEMENT,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_PAYOUT_BEFORE_SETTLEMENT,
        ],
    ],

    'testPayoutAttemptSuccess' => [
        'channel'        => 'yesbank',
        'version'        => 'V3',
        'status'         => FundTransferAttemptStatus::PROCESSED,
        'remarks'        => '',
        'failure_reason' => null,
    ],

    'testPayoutEntitySuccess' => [
        'channel'        => 'yesbank',
        'status'         => PayoutStatus::PROCESSED,
        'remarks'        => '',
        'failure_reason' => null,
        'settled_on'     => null,
    ],

    'testPayoutAttemptReconSuccess' => [
        'channel'          => 'yesbank',
        'version'          => 'V3',
        'bank_status_code' => 'P',
        'status'           => FundTransferAttemptStatus::INITIATED,
    ],

    'testCreateMerchantPayoutOnDemand' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/merchant/payout/demand',
            'content' => [
                'amount'   => 1000,
                'currency' => 'INR'
            ],
        ],
        'response' => [
            'content' => [
                'entity'      => 'payout',
                'amount'      => 398,
                'currency'    => 'INR',
                'tax'         => 92,
                'fees'        => 602,
                'notes'       => []
            ],
        ],
    ],
    'testCreateMerchantPayoutOnDemandOnLowBalance' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/merchant/payout/demand',
            'content' => [
                'amount'   => 1000,
                'currency' => 'INR'
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Your account does not have enough balance to carry out the payout operation.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYOUT_NOT_ENOUGH_BALANCE_BANKING,
        ],
    ],
    'testCreateMerchantPayoutOnHoldFunds' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/merchant/payout/demand',
            'content' => [
                'amount'   => 1000,
                'currency' => 'INR'
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'This operation is not allowed. Please contact Razorpay support for details.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_MERCHANT_FUNDS_ON_HOLD,
        ],
    ],
    'testCreateMerchantPayoutOnMinAmount' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/merchant/payout/demand',
            'content' => [
                'amount'   => 105,
                'currency' => 'INR'
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Payout amount including fees should be greater than Re 1',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYOUT_LESS_THAN_MIN_AMOUNT,
        ],
    ],

    'testSearchPayoutByTransactionId' => [
        'request' => [
            'method'  => 'get',
            'url'     => '/payouts',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
            ]
        ]
    ],

    'testSearchPayoutByUtr' => [
        'request' => [
            'method'  => 'get',
            'url'     => '/payouts',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
            ]
        ]
    ],

    'testSearchPayoutByContactId' => [
        'request' => [
            'method'  => 'get',
            'url'     => '/payouts',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
            ]
        ]
    ],

    'testSearchPayoutByContactName' => [
        'request' => [
            'method'  => 'get',
            'url'     => '/payouts',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
            ]
        ]
    ],

    'testSearchPayoutByContactPhone' => [
        'request' => [
            'method'  => 'get',
            'url'     => '/payouts',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
            ]
        ]
    ],

    'testSearchPayoutByContactEmail' => [
        'request' => [
            'method'  => 'get',
            'url'     => '/payouts',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
            ]
        ]
    ],

    'testSearchPayoutByFundAccountId' => [
        'request' => [
            'method'  => 'get',
            'url'     => '/payouts',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
            ]
        ]
    ],

    'testSearchPayoutByPayoutId' => [
        'request' => [
            'method'  => 'get',
            'url'     => '/payouts',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
            ]
        ]
    ],

    'testSearchPayoutByPayoutStatus' => [
        'request' => [
            'method'  => 'get',
            'url'     => '/payouts',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
            ]
        ]
    ],

    'testSearchPayoutByPayoutContactType' => [
        'request' => [
            'method'  => 'get',
            'url'     => '/payouts',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
            ]
        ]
    ],
];
