<?php

return [

    'testPostCreate' => [
        'request' => [
            'url' => '/d2c_bureau_details',
            'method' => 'post',
        ],
        'response' => [
            'content' => [
//                'id'                => 'd2cbd_DVPO2EfMdU2inS',
                'first_name'        => 'testhello',
                'contact_mobile'    => '9876543210',
//                'email'             => 'tabitha.damore@mraz.biz',
                'address'           => 'Adress',
                'city'              => 'city',
                'pincode'           => '123455',
                'pan'               => 'ABCDE1234F',
//                'created_at'        => 1571374473
            ],
        ],
    ],

    'testPatchBureauDetails' => [
        'request' => [
            'url' => '/d2c_bureau_details/',
            'method'    => 'patch',
            'content'   => [
                'first_name'    => 'john',
                'last_name'     => 'doe',
                'email'         => 'test@razorpay.com',
                'state'         => 'PB',
                'date_of_birth' => '1996-10-10',
                'gender'        => 'male'
            ]
        ],
        'response' => [
            'content' => [
//                'id'                => 'd2cbd_DVPO2EfMdU2inS',
                'first_name'        => 'john',
                'last_name'         => 'doe',
                'contact_mobile'    => '9876543210',
                'email'             => 'test@razorpay.com',
                'address'           => 'Adress',
                'city'              => 'city',
                'state'             => 'PB',
                'pincode'           => '123455',
                'pan'               => 'ABCDE1234F',
//                'created_at'        => 1571374473
            ],
        ],
    ],

    'testSubmitOtp' => [
        'request' => [
            'url' => '/d2c_bureau_details/{id}/otp_submit',
            'method'    => 'post',
            'content'   => [
                'otp'           => '0007',
                'token'         => 'BUIj3m2Nx2VvVj',
            ]
        ],
        'response' => [
            'content' => [
//             id' =>  "d2c_Da2dJt1XFev9Oh"
                'provider'          => 'experian',
                'score'             => 752,
                'report'            => '{"active_accounts":"1","closed_accounts":"1","count_of_accounts":"2","secured_account_outstanding_balance":"152000","total_outstanding_balance":"152000","un_secured_account_outstanding_balance":"0"}',
                'max_loan_amount'   => null,
//                'created_at' => 1572386045
            ],
        ],
    ],

    'testPatchBureauReport' => [
        'request' => [
            'url' => '/d2c_bureau_reports/',
            'method'    => 'patch',
            'content'   => [
                'interested'    => 1,
            ]
        ],
        'response' => [
            'content' => [
//                'id'                => 'd2c_Dg8DrxoP8KXelQ',
                'provider'          => 'experian',
                'score'             => 752,
                'report'            => '{"active_accounts": "1", "closed_accounts": "1", "count_of_accounts": "2", "total_outstanding_balance": "152000", "secured_account_outstanding_balance": "152000", "un_secured_account_outstanding_balance": "0"}',
                'interested'        => true,
//                'created_at'        => 1571374473
            ],
        ],
    ]
];
