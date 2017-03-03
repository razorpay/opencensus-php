<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

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
                'service_tax' => 77,
                'fees'        => 587,
                'notes'       => [
                    'abc' => 'xyz',
                ],
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
                    'description' => PublicErrorDescription::BAD_REQUEST_PAYOUT_MERCHANT_FUNDS_ON_HOLD,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYOUT_MERCHANT_FUNDS_ON_HOLD,
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
                'service_tax' => 77,
                'fees'        => 587,
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
                'service_tax' => 78,
                'fees'        => 598,
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
];
