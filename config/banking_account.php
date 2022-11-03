<?php

return [
    'rbl' => [
        'client_id'         => env('BANKING_ACCOUNT_RBL_CLIENT_ID'),
        'client_secret'     => env('BANKING_ACCOUNT_RBL_CLIENT_SECRET'),
        'username'          => env('BANKING_ACCOUNT_RBL_USERNAME'),
        'password'          => env('BANKING_ACCOUNT_RBL_PASSWORD'),
        'mozart_identifier' => env('BANKING_ACCOUNT_RBL_MOZART_IDENTIFIER')
    ],

    'razorpayx_fee_details' => [
        'name'              => env('RZP_FEES_DETAILS_NAME'),
        'rbl'               => [
            'account_number'    => env('RZP_FEES_DETAILS_ACCOUNT_NUMBER'),
            'ifsc'              => env('RZP_FEES_DETAILS_IFSC'),
        ],
        'icici'             => [
            'account_number'    => env('RZP_FEES_DETAILS_ICICI_ACCOUNT_NUMBER'),
            'ifsc'              => env('RZP_FEES_DETAILS_ICICI_IFSC'),
        ],
        'axis'              => [
            'account_number'    => env('RZP_FEES_DETAILS_AXIS_ACCOUNT_NUMBER'),
            'ifsc'              => env('RZP_FEES_DETAILS_AXIS_IFSC'),
        ],
        'yesbank'              => [
            'account_number'    => env('RZP_FEES_DETAILS_YESBANK_ACCOUNT_NUMBER'),
            'ifsc'              => env('RZP_FEES_DETAILS_YESBANK_IFSC'),
        ],
    ],

    'icici' => [
        'aggr_id'             => env('ICICI_AGGR_ID'),
        'aggr_name'           => env('ICICI_AGGR_NAME'),
        'beneficiary_api_key' => env('ICICI_BENEFICIARY_API_KEY'),
    ],
    'razorpay_fund_addition_accounts' => [
        'fee_credit' => [
            'merchant_id' => env('RZP_FEE_CREDIT_MERCHANT_ID'),
        ],
        'refund_credit' => [
            'merchant_id' => env('RZP_REFUND_CREDIT_MERCHANT_ID'),
        ],
        'reserve_balance' => [
            'merchant_id' => env('RZP_RESERVE_BALANCE_MERCHANT_ID'),
        ]
    ]
];
