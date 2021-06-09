<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;

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
                'pan'               => 'ABCPE1234F',
//                'created_at'        => 1571374473
            ],
        ],
    ],

    'testPostCreateInternal' => [
        'request' => [
            'url' => '/los/d2c_bureau_details',
            'method' => 'post',
            'content'   => [
                'merchant_id'   => '10000000000000',
                'user_id'       => '20000000000000',
                'otp'           => '0007',
                'token'         => 'BUIj3m2Nx2VvVj',
                'd2c_bureau_detail'    => [
                    'first_name'      => 'john',
                    'last_name'       => 'doe',
                    'contact_mobile'  => '9999999999',
                    'email'           => 'test@razorpay.com',
                    'address'         => 'Adress',
                    'city'            => 'city',
                    'state'           => 'PB',
                    'pincode'         => '560030',
                    'pan'             => 'ABCPE1234F',
                    'date_of_birth'   => '1996-10-10',
                    'gender'          => 'male'
                ],
            ],
        ],
        'response' => [
            'content' => [
//                'id'                => 'd2c_Dg8DrxoP8KXelQ',
                'provider'          => 'experian',
                'score'             => 752,
                'ntc_score'         => null,
                'report'            => [
                    'active_accounts'                           => '1',
                    'closed_accounts'                           => '1',
                    'count_of_accounts'                         => '2',
                    'total_outstanding_balance'                 => '152000',
                    'secured_account_outstanding_balance'       => '152000',
                    'un_secured_account_outstanding_balance'    => '0',
                ],
//                'created_at'        => 1571374473
            ],
        ],
    ],

    'testReportDelete'  => [
        'request' => [
            'url' => '/d2c_bureau_reports/',
            'method' => 'delete',
            'content'   => [
            ],
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testPostCreateInternalWithExperianFailure' => [
        'request' => [
            'url' => '/los/d2c_bureau_details',
            'method' => 'post',
            'content'   => [
                'merchant_id'   => '10000000000000',
                'user_id'       => '20000000000000',
                'otp'           => '0007',
                'token'         => 'BUIj3m2Nx2VvVj',
                'd2c_bureau_detail'    => [
                    'first_name'      => 'john',
                    'last_name'       => 'doe',
                    'contact_mobile'  => '9999999999',
                    'email'           => 'test@razorpay.com',
                    'address'         => 'Adress',
                    'city'            => 'city',
                    'state'           => 'PB',
                    'pincode'         => '560030',
                    'pan'             => 'ABCPE1234F',
                    'date_of_birth'   => '1996-10-10',
                    'gender'          => 'male'
                ],
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => 'BAD_REQUEST_ERROR',
                ]

            ],
            'status_code'   => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\GatewayErrorException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_D2C_CREDIT_BUREAU_NO_RECORDS_FOUND,
        ],
    ],

    'testFetchBureauReportWithInvalidContactFailure' => [
        'request' => [
            'url' => '/los/d2c_bureau_details',
            'method' => 'post',
            'content'   => [
                'merchant_id'   => '10000000000000',
                'user_id'       => '20000000000000',
                'otp'           => '0007',
                'token'         => 'BUIj3m2Nx2VvVj',
                'd2c_bureau_detail'    => [
                    'first_name'      => 'john',
                    'last_name'       => 'doe',
                    'contact_mobile'  => '9999999999',
                    'email'           => 'test@razorpay.com',
                    'address'         => 'Adress',
                    'city'            => 'city',
                    'state'           => 'PB',
                    'pincode'         => '560030',
                    'pan'             => 'ABCPE1234F',
                    'date_of_birth'   => '1996-10-10',
                    'gender'          => 'male'
                ],
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'No records found for this phone number. Please try again with 99XXXXX243',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_D2C_CREDIT_BUREAU_INVALID_EMAIL_OR_CONTACT,
        ],
    ],

    'testFetchBureauReportWithInvalidContactFailureNoPhoneNumber' => [
        'request' => [
            'url' => '/los/d2c_bureau_details',
            'method' => 'post',
            'content'   => [
                'merchant_id'   => '10000000000000',
                'user_id'       => '20000000000000',
                'otp'           => '0007',
                'token'         => 'BUIj3m2Nx2VvVj',
                'd2c_bureau_detail'    => [
                    'first_name'      => 'john',
                    'last_name'       => 'doe',
                    'contact_mobile'  => '9999999999',
                    'email'           => 'test@razorpay.com',
                    'address'         => 'Adress',
                    'city'            => 'city',
                    'state'           => 'PB',
                    'pincode'         => '560030',
                    'pan'             => 'ABCDE1234F',
                    'date_of_birth'   => '1996-10-10',
                    'gender'          => 'male'
                ],
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'No records found for this phone number. Please contact support - capital.support@razorpay.com',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_D2C_CREDIT_BUREAU_INVALID_EMAIL_OR_CONTACT,
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
                'pan'               => 'ABCPE1234F',
//                'created_at'        => 1571374473
            ],
        ],
    ],

    'testFetchBureauReportWithInternalAuth' => [
        'request' => [
            'url' => '/los/d2c_bureau_reports',
            'method'    => 'get',
            'content'   => [
                'merchant_id'   => '10000000000000',
                'user_id'       => '20000000000000',
            ],
        ],
        'response' => [
            'content' => [
//                'id'                => 'd2c_Dg8DrxoP8KXelQ',
                'provider'          => 'experian',
                'score'             => 752,
                'ntc_score'         => null,
                'report'            => [
                    'active_accounts'                           => '1',
                    'closed_accounts'                           => '1',
                    'count_of_accounts'                         => '2',
                    'total_outstanding_balance'                 => '152000',
                    'secured_account_outstanding_balance'       => '152000',
                    'un_secured_account_outstanding_balance'    => '0',
                ],
                'interested'        => null,
                'request_object'    => [
                    'first_name' => 'john',
                    'last_name' => 'doe',
                    'date_of_birth' => '1996-10-10',
                    'gender' => 'male',
                    'contact_mobile' => '9876543210',
                    'email' => 'test@razorpay.com',
                    'address' => 'Adress',
                    'city' => 'city',
                    'state' => 'PB',
                    'pincode' => '123455',
                    'pan' => 'ABCPE1234F',
                ]
//                'created_at'        => 1571374473
            ],
        ],
    ],

    'testFetchBureauReportWithLowerCasePanInternalAuth' => [
        'request' => [
            'url' => '/los/d2c_bureau_reports',
            'method'    => 'get',
            'content'   => [
                'merchant_id'   => '10000000000000',
                'user_id'       => 'qjakcliequield',
                'pan'           => 'ARhPP7770L',
            ],
        ],
        'response' => [
            'content' => [
                'provider'          => 'EXPERIAN',
                'score'             => 752,
                'ntc_score'         => null,
                'interested'        => null,
                'request_object'    => [
                    'first_name'                  => 'srikant',
                    'last_name'                   => 'tiwari',
                    'pan'                         => 'Arhpp7770l',
                ]
            ],
        ],
    ],

    'testFetchBureauReportWithInternalAuthNtc' => [
        'request' => [
            'url' => '/los/d2c_bureau_reports',
            'method'    => 'get',
            'content'   => [
                'merchant_id'   => '10000000000000',
                'user_id'       => '20000000000000',
            ],
        ],
        'response' => [
            'content' => [
//                'id'                => 'd2c_Dg8DrxoP8KXelQ',
                'provider'          => 'experian',
                'score'             => null,
                'ntc_score'         => '4',
                'report'            => null,
                'interested'        => null,
//                'created_at'        => 1571374473
            ],
        ],
    ],

    'testNtcFlow' => [
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
                'score'             => null,
                'ntc_score'         => 4,
                'report'            => null,
                'max_loan_amount'   => null,
//                'created_at' => 1572386045
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
                'ntc_score'         => null,
                'report'            => [
                    'active_accounts'                           => '1',
                    'closed_accounts'                           => '1',
                    'count_of_accounts'                         => '2',
                    'total_outstanding_balance'                 => '152000',
                    'secured_account_outstanding_balance'       => '152000',
                    'un_secured_account_outstanding_balance'    => '0',
                ],
                'max_loan_amount'   => null,
//                'created_at' => 1572386045
            ],
        ],
    ],

    'testSubmitOtpInternal' => [
        'request' => [
            'url' => '/los/d2c_bureau_details/{id}/otp_submit',
            'method'    => 'post',
            'content'   => [
                'merchant_id'   => '10000000000000',
                'user_id'       => '20000000000000',
                'otp'           => '0007',
                'token'         => 'BUIj3m2Nx2VvVj',
            ]
        ],
        'response' => [
            'content' => [
//             id' =>  "d2c_Da2dJt1XFev9Oh"
                'provider'          => 'experian',
                'score'             => 752,
                'ntc_score'         => null,
                'report'            => [
                    'active_accounts'                           => '1',
                    'closed_accounts'                           => '1',
                    'count_of_accounts'                         => '2',
                    'total_outstanding_balance'                 => '152000',
                    'secured_account_outstanding_balance'       => '152000',
                    'un_secured_account_outstanding_balance'    => '0',
                ],
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
                'ntc_score'         => null,
                'report'            => [
                    'active_accounts'                           => '1',
                    'closed_accounts'                           => '1',
                    'count_of_accounts'                         => '2',
                    'total_outstanding_balance'                 => '152000',
                    'secured_account_outstanding_balance'       => '152000',
                    'un_secured_account_outstanding_balance'    => '0',
                ],
                'interested'        => true,
//                'created_at'        => 1571374473
            ],
        ],
    ],

    'testGetDownloadUrl' => [
        'request' => [
            'url' => '/d2c_bureau_reports/{id}/download_url',
            'method'    => 'get',
        ],
        'response' => [
            'content' => [
//              'signed_url' => 'report_experian_d2cbd_EKTOtrqmCOoNhF.txt.txt',
                'csv_signed_url' =>'rzp_file_mock_id_1000000_bureau_report_csv',
            ],
        ],
    ],
    'testGetDownloadUrlForNtc' => [
        'request' => [
            'url' => '/d2c_bureau_reports/{id}/download_url',
            'method'    => 'get',
        ],
        'response' => [
            'content' => [
                'signed_url'     => null,
                'csv_signed_url' => null,
            ],
        ],
    ]
];
