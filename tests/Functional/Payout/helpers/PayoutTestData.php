<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;
use RZP\Models\FundTransfer\Attempt\Status as FundTransferAttemptStatus;
use RZP\Models\Payout\Status as PayoutStatus;

return [
    'testCreatePayout' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'amount'      => 1000,
                'currency'    => 'INR',
                'customer_id' => 'cust_100000customer',
                'method'      => 'fund_transfer',
                'destination' => 'ba_1000000lcustba',
                'notes'       => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'      => 'payout',
                'amount'      => 1000,
                'currency'    => 'INR',
                'customer_id' => 'cust_100000customer',
                'method'      => 'fund_transfer',
                'destination' => 'ba_1000000lcustba',
                'tax'         => 92,
                'fees'        => 602,
                'notes'       => [
                    'abc' => 'xyz',
                ],
            ],
        ],
    ],

    'testCreateMerchantPayout' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/merchant/payout',
            'content' => [
                'amount'         => 1000,
                'merchant_id'    => '10000000000000',
                'customer_id'    => 'cust_100000customer',
                'destination_id' => 'ba_1000000lcustba',
            ],
        ],
        'response' => [
            'content' => [
                'entity'      => 'payout',
                'amount'      => 1000,
                'currency'    => 'INR',
                'customer_id' => 'cust_100000customer',
                'method'      => 'fund_transfer',
                'destination' => 'ba_1000000lcustba',
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
                'customer_id'    => 'cust_100000customer',
                'destination_id' => 'ba_1000000lcustba',
                'modulo'         => 1000
            ],
        ],
        'response' => [
            'content' => [
                'entity'      => 'payout',
                'amount'      => 1000,
                'currency'    => 'INR',
                'customer_id' => 'cust_100000customer',
                'method'      => 'fund_transfer',
                'destination' => 'ba_1000000lcustba',
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
                'customer_id'    => 'cust_100000customer',
                'destination_id' => 'ba_1000000lcustba',
                'min_amount'     => 3000,
                'modulo'         => 1000
            ],
        ],
        'response' => [
            'content' => [
                'message' => 'amount to be transferred is less than 3000'
            ],
        ],
    ],

    'testCreatePayoutFundsOnHold' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/payouts',
            'content' => [
                'amount'      => 1000000,
                'currency'    => 'INR',
                'customer_id' => 'cust_100000customer',
                'method'      => 'fund_transfer',
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
                'amount'      => 1000000,
                'currency'    => 'INR',
                'customer_id' => 'cust_100000customer',
                'method'      => 'fund_transfer',
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
                    'description' => PublicErrorDescription::BAD_REQUEST_PAYOUT_NOT_ENOUGH_BALANCE,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYOUT_NOT_ENOUGH_BALANCE,
        ],
    ],

    'testGetPayouts' => [
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
                'amount'      => 1000,
                'currency'    => 'INR',
                'customer_id' => 'cust_100000customer',
                'method'      => 'fund_transfer',
                'destination' => 'ba_1000000lcustba',
                'notes'       => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'      => 'payout',
                'amount'      => 1000,
                'currency'    => 'INR',
                'customer_id' => 'cust_100000customer',
                'destination' => 'fund_transfer',
                'destination' => 'ba_1000000lcustba',
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
                'method'      => 'fund_transfer',
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
        'request' => [
            'method'  => 'POST',
            'url'     => '/payments/{id}/payout',
            'content' => [
                'amount'      => 2000,
                'currency'    => 'INR',
                'customer_id' => 'cust_100000customer',
                'method'      => 'fund_transfer',
                'destination' => 'ba_1000000lcustba',
                'notes'       => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'      => 'payout',
                'amount'      => 2000,
                'currency'    => 'INR',
                'customer_id' => 'cust_100000customer',
                'destination' => 'fund_transfer',
                'destination' => 'ba_1000000lcustba',
                'tax'         => 94,
                'fees'        => 614,
                'notes'       => [
                    'abc' => 'xyz',
                ],
            ],
        ]
    ],

    'testCreatePaymentPayoutNotSettledLiveMode' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/payments/{id}/payout',
            'content' => [
                'amount'      => 1000,
                'currency'    => 'INR',
                'customer_id' => 'cust_100000customer',
                'method'      => 'fund_transfer',
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

    'testCreateBankAccountPayoutOnCardPayment' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/payments/{id}/payout',
            'content' => [
                'amount'      => 1000,
                'currency'    => 'INR',
                'customer_id' => 'cust_100000customer',
                'method'      => 'fund_transfer',
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
                    'description' => PublicErrorDescription::BAD_REQUEST_FUND_TRANSFER_PAYOUT_ON_CREDIT_CARD_PAYMENT,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_FUND_TRANSFER_PAYOUT_ON_CREDIT_CARD_PAYMENT,
        ],
    ],

    'testPayoutAttemptSuccess' => [
        'channel' => 'kotak',
        'version' => 'V3',
        'status' => FundTransferAttemptStatus::INITIATED,
        'utr' => NULL,
        'remarks' => NULL,
        'failure_reason' => NULL,
    ],

    'testPayoutInitiateSuccess' => [
        'channel' => 'kotak',
        'status' => PayoutStatus::INITIATED,
        'utr' => NULL,
        'remarks' => NULL,
        'failure_reason' => NULL,
        'processed_at' => NULL,
        'settled_on' => NULL,
    ],

    'testPayoutAttemptReconSuccess' => [
        'channel' => 'kotak',
        'version' => 'V3',
        'bank_status_code'  => 'P',
    ],
];
