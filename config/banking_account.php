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
        'account_number'    => env('RZP_FEES_DETAILS_ACCOUNT_NUMBER'),
        'ifsc'              => env('RZP_FEES_DETAILS_IFSC'),
    ],

    'icici' => [
        'aggr_id'             => env('ICICI_AGGR_ID'),
        'aggr_name'           => env('ICICI_AGGR_NAME'),
        'beneficiary_api_key' => env('ICICI_BENEFICIARY_API_KEY'),
    ]
];
