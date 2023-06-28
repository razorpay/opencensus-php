<?php

namespace RZP\Tests\Functional\BankingAccountTpv;

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;
use RZP\Models\BankingAccountTpv\Status;

return [
    'testAdminTpvCreate' => [
        'request'  => [
            'url'     => '/admin/tpv/create',
            'method'  => 'post',
            'content' => [
                'merchant_id'          => '10000000000000',
                'balance_id'           => '10000000000000',
                'status'               => Status::APPROVED,
                'payer_name'           => 'Razorpay',
                'payer_account_number' => '98711120003344',
                'payer_ifsc'           => 'CITI0000006',
                'created_by'           => 'OPS_A',
            ],
        ],
        'response' => [
            'content' => [
                'merchant_id'          => '10000000000000',
                'balance_id'           => '10000000000000',
                'status'               => Status::APPROVED,
                'payer_name'           => 'Razorpay',
                'payer_account_number' => '98711120003344',
                'payer_ifsc'           => 'CITI0000006',
                'is_active'            => true,
                'type'                 => 'bank_account',
            ],
        ],
    ],

    'testAdminTpvCreateWitInvalidMerchantBalanceId' => [
        'request'  => [
            'url'     => '/admin/tpv/create',
            'method'  => 'post',
            'content' => [
                'merchant_id'          => '10000000000111',
                'balance_id'           => '10000000000000',
                'status'               => Status::APPROVED,
                'payer_name'           => 'Razorpay',
                'payer_account_number' => '98711120003344',
                'payer_ifsc'           => 'CITI0000006',
                'created_by'           => 'OPS_A',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_TPV_INVALID_MERCHANT_BALANCE_ID,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_TPV_INVALID_MERCHANT_BALANCE_ID,
        ],
    ],

    'testCreateTpvWithDirectBalanceException' => [
        'request'  => [
            'url'     => '/admin/tpv/create',
            'method'  => 'post',
            'content' => [
                'merchant_id'          => '10000000000000',
                'balance_id'           => '10000000000000',
                'status'               => Status::APPROVED,
                'payer_name'           => 'Razorpay',
                'payer_account_number' => '98711120003344',
                'payer_ifsc'           => 'CITI0000006',
                'created_by'           => 'OPS_A',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_TPV_BALANCE_TYPE_DIRECT_NOT_SUPPORTED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_TPV_BALANCE_TYPE_DIRECT_NOT_SUPPORTED,
        ],
    ],

    'testCreateTpvWithPrimaryBalanceException' => [
        'request'  => [
            'url'     => '/admin/tpv/create',
            'method'  => 'post',
            'content' => [
                'merchant_id'          => '10000000000000',
                'balance_id'           => '100Balance1111',
                'status'               => Status::APPROVED,
                'payer_name'           => 'Razorpay',
                'payer_account_number' => '98711120003344',
                'payer_ifsc'           => 'CITI0000006',
                'created_by'           => 'OPS_A',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_TPV_PRIMARY_BALANCE_NOT_SUPPORTED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_TPV_PRIMARY_BALANCE_NOT_SUPPORTED,
        ],
    ],

    'testCreateTpvWithInvalidBalanceException' => [
        'request'  => [
            'url'     => '/admin/tpv/create',
            'method'  => 'post',
            'content' => [
                'merchant_id'          => '10000000000000',
                'balance_id'           => '10000002223334',
                'status'               => Status::APPROVED,
                'payer_name'           => 'Razorpay',
                'payer_account_number' => '98711120003344',
                'payer_ifsc'           => 'CITI0000006',
                'created_by'           => 'OPS_A',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_TPV_ERROR,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_TPV_ERROR,
        ],
    ],

    'testAdminTpvCreateDuplicateException' => [
        'request'   => [
            'url'     => '/admin/tpv/create',
            'method'  => 'post',
            'content' => [
                'merchant_id'          => '10000000000000',
                'balance_id'           => '10000000000000',
                'status'               => Status::APPROVED,
                'payer_name'           => 'Razorpay',
                'payer_account_number' => '98711120003344',
                'payer_ifsc'           => 'CITI0000006',
                'created_by'           => 'OPS_A',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_DUPLICATE_TPV,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_DUPLICATE_TPV,
        ],
    ],

    'testXDashboardTpvCreateDuplicateException' => [
        'request'  => [
            'url'     => '/merchant/tpv',
            'method'  => 'post',
            'content' => [
                'balance_id'           => '10000000000000',
                'payer_name'           => 'Razorpay',
                'payer_account_number' => '98711120003344',
                'payer_ifsc'           => 'CITI0000006',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_DUPLICATE_TPV,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_DUPLICATE_TPV,
        ],
    ],

    'testGetMerchantTpvs' => [
        'request'  => [
            'url'     => '/merchant/tpvs',
            'method'  => 'get',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 2,
                'items'  => [
                    [
                        'merchant_id'          => '10000000000000',
                        'balance_id'           => '10000000000000',
                        'status'               => Status::APPROVED,
                        'payer_name'           => 'Razorpay',
                        'payer_account_number' => '98711120003344',
                        'payer_ifsc'           => 'CITI0000006',
                        'created_by'           => 'OPS_A',
                        'bank_name'            => 'CITI Bank',
                        'type'                 => 'bank_account',
                        'is_active'            => true,
                    ],
                    [
                        'merchant_id'          => '10000000000000',
                        'balance_id'           => '10000000000000',
                        'status'               => Status::REJECTED,
                        'payer_name'           => 'Razorpay',
                        'payer_account_number' => '8927398273',
                        'payer_ifsc'           => 'CITI0000006',
                        'created_by'           => 'OPS_A',
                        'bank_name'            => 'CITI Bank',
                        'type'                 => 'bank_account',
                        'is_active'            => false,
                        'remarks'              => 'Invalid docs'
                    ],
                ],
            ],
        ],
    ],

    'testLimitOnSourceAccounts' => [
        'request'  => [
            'url'     => '/merchant/tpv',
            'method'  => 'post',
            'content' => [
                'balance_id'           => '10000000000000',
                'payer_name'           => 'Razorpay',
                'payer_account_number' => '98711120003344',
                'payer_ifsc'           => 'CITI0000006',
            ],
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testFetchMerchantTpvsWithNoRecords' => [
        'request'  => [
            'url'     => '/merchant/tpvs',
            'method'  => 'get',
            'content' => [],
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testAdminEditTpv' => [
        'request'  => [
            'url'     => '/admin/tpv/',
            'method'  => 'patch',
            'content' => [
                'merchant_id'          => '10000000000000',
                'balance_id'           => '10000000000000',
                'payer_account_number' => '98711120003344',
                'status'               => Status::REJECTED,
                'remarks'              => 'Morphed docs',
                'payer_ifsc'           => 'CITI0000006',
            ],
        ],
        'response' => [
            'content' => [
                'merchant_id'          => '10000000000000',
                'balance_id'           => '10000000000000',
                'status'               => Status::REJECTED,
                'payer_name'           => 'Razorpay',
                'payer_account_number' => '98711120003344',
                'payer_ifsc'           => 'CITI0000006',
                'is_active'            => false,
                'type'                 => 'bank_account',
                'remarks'              => 'Morphed docs'
            ],
        ],
    ],

    'testAdminEditTpvWitInvalidMerchantBalanceId' => [
        'request'  => [
            'url'     => '/admin/tpv/',
            'method'  => 'patch',
            'content' => [
                'merchant_id'          => '10000000002222',
                'balance_id'           => '10000000000000',
                'payer_account_number' => '98711120003344',
                'status'               => Status::REJECTED,
                'remarks'              => 'Morphed docs',
                'payer_ifsc'           => 'CITI0000006',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_TPV_INVALID_MERCHANT_BALANCE_ID,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_TPV_INVALID_MERCHANT_BALANCE_ID,
        ],
    ],

    'testAdminEditTpvInvalidAccountNumber' => [
        'request'   => [
            'url'     => '/admin/tpv/',
            'method'  => 'patch',
            'content' => [
                'merchant_id'          => '10000000000000',
                'balance_id'           => '10000000000000',
                'payer_account_number' => '9871',
                'status'               => Status::REJECTED,
                'remarks'              => 'Morphed docs',
                'payer_ifsc'           => 'CITI0000006',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The payer account number must be between 5 and 40 characters.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testAdminEditTpvStatusUpdate' => [
        'request'  => [
            'url'     => '/admin/tpv/',
            'method'  => 'patch',
            'content' => [
                'status'               => Status::REJECTED,
            ],
        ],
        'response' => [
            'content' => [
                'merchant_id'          => '10000000000000',
                'balance_id'           => '10000000000000',
                'status'               => Status::REJECTED,
                'payer_name'           => 'Razorpay',
                'payer_account_number' => '98711120003344',
                'payer_ifsc'           => 'CITI0000006',
                'is_active'            => false,
                'type'                 => 'bank_account',
            ],
        ],
    ],

    'testFetchMerchantTpvsWithFavInfo' => [
        'request'  => [
            'url'     => '/admin/merchant/10000000000000/tpvs?count=5&skip=0',
            'method'  => 'get',
            'content' => [],
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testManualAutoApproveTpv' => [
        'request'  => [
            'url'     => '/admin/merchants/tpv_bulk_create',
            'method'  => 'post',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testManualAutoApproveTpvFail' => [
        'request'  => [
            'url'     => '/admin/merchants/tpv_bulk_create',
            'method'  => 'post',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testCreateTpvFromXDashboard' => [
        'request'  => [
            'url'     => '/merchant/tpv',
            'method'  => 'post',
            'content' => [
                'balance_id'           => '10000000000000',
                'payer_name'           => 'Razorpay',
                'payer_account_number' => '98711120003344',
                'payer_ifsc'           => 'CITI0000006',
            ],
        ],
        'response' => [
            'content' => [
                'merchant_id'          => '10000000000000',
                'balance_id'           => '10000000000000',
                'status'               => Status::PENDING,
                'payer_name'           => 'Razorpay',
                'payer_account_number' => '98711120003344',
                'payer_ifsc'           => 'CITI0000006',
                'is_active'            => false,
                'type'                 => 'bank_account',
            ],
        ],
    ],

    'testCreateTpvFromXDashboardWitInvalidMerchantBalanceId' => [
        'request'  => [
            'url'     => '/merchant/tpv',
            'method'  => 'post',
            'content' => [
                'balance_id'           => '10000000000000',
                'payer_name'           => 'Razorpay',
                'payer_account_number' => '98711120003344',
                'payer_ifsc'           => 'CITI0000006',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_TPV_INVALID_MERCHANT_BALANCE_ID,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_TPV_INVALID_MERCHANT_BALANCE_ID,
        ],
    ],

    'testCreateTpvFromXDashboardAdminUser' => [
        'request'  => [
            'url'     => '/merchant/tpv',
            'method'  => 'post',
            'content' => [
                'balance_id'           => '10000000000000',
                'payer_name'           => 'Razorpay',
                'payer_account_number' => '98711120003344',
                'payer_ifsc'           => 'CITI0000006',
            ],
        ],
        'response' => [
            'content'     => [
                'merchant_id'          => '10000000000000',
                'balance_id'           => '10000000000000',
                'status'               => Status::PENDING,
                'payer_name'           => 'Razorpay',
                'payer_account_number' => '98711120003344',
                'payer_ifsc'           => 'CITI0000006',
                'is_active'            => false,
                'type'                 => 'bank_account',
            ],
        ],
    ],

    'testAdminTpvCreateWithPrependedZerosInPayerAccountNumber' => [
        'request'  => [
            'url'     => '/admin/tpv/create',
            'method'  => 'post',
            'content' => [
                'merchant_id'          => '10000000000000',
                'balance_id'           => '10000000000000',
                'status'               => Status::APPROVED,
                'payer_name'           => 'Razorpay',
                'payer_account_number' => '00098711120003344',
                'payer_ifsc'           => 'CITI0000006',
                'created_by'           => 'OPS_A',
            ],
        ],
        'response' => [
            'content' => [
                'merchant_id'          => '10000000000000',
                'balance_id'           => '10000000000000',
                'status'               => Status::APPROVED,
                'payer_name'           => 'Razorpay',
                'payer_account_number' => '00098711120003344',
                'payer_ifsc'           => 'CITI0000006',
                'is_active'            => true,
                'type'                 => 'bank_account',
            ],
        ],
    ],

    'testCreateTpvFromXDashboardAdminUserWithPrependedZerosInPayerAccountNumber' => [
        'request'  => [
            'url'     => '/merchant/tpv',
            'method'  => 'post',
            'content' => [
                'balance_id'           => '10000000000000',
                'payer_name'           => 'Razorpay',
                'payer_account_number' => '098711120003344',
                'payer_ifsc'           => 'CITI0000006',
            ],
        ],
        'response' => [
            'content'     => [
                'merchant_id'          => '10000000000000',
                'balance_id'           => '10000000000000',
                'status'               => Status::PENDING,
                'payer_name'           => 'Razorpay',
                'payer_account_number' => '098711120003344',
                'payer_ifsc'           => 'CITI0000006',
                'is_active'            => false,
                'type'                 => 'bank_account',
            ],
        ],
    ],

    'testAdminEditTpvWithPrependedZerosInPayerAccountNumber' => [
        'request'  => [
            'url'     => '/admin/tpv/',
            'method'  => 'patch',
            'content' => [
                'merchant_id'          => '10000000000000',
                'balance_id'           => '10000000000000',
                'payer_account_number' => '00987431120003344',
                'status'               => Status::REJECTED,
                'remarks'              => 'Morphed docs',
                'payer_ifsc'           => 'CITI0000006',
            ],
        ],
        'response' => [
            'content' => [
                'merchant_id'          => '10000000000000',
                'balance_id'           => '10000000000000',
                'status'               => Status::REJECTED,
                'payer_name'           => 'Razorpay',
                'payer_account_number' => '00987431120003344',
                'payer_ifsc'           => 'CITI0000006',
                'is_active'            => false,
                'type'                 => 'bank_account',
                'remarks'              => 'Morphed docs'
            ],
        ],
    ],

    'testAdminTpvCreateDuplicateWithPrependedZerosException' => [
        'request'   => [
            'url'     => '/admin/tpv/create',
            'method'  => 'post',
            'content' => [
                'merchant_id'          => '10000000000000',
                'balance_id'           => '10000000000000',
                'status'               => Status::APPROVED,
                'payer_name'           => 'Razorpay',
                'payer_account_number' => '0000098711120003344',
                'payer_ifsc'           => 'CITI0000006',
                'created_by'           => 'OPS_A',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_DUPLICATE_TPV,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_DUPLICATE_TPV,
        ],
    ],

    'testDisableTpvForLiveDisabledMerchant' => [
        'request'  => [
            'url'     => '/merchant/tpv',
            'method'  => 'post',
            'content' => [
                'balance_id'           => '10000000000000',
                'payer_name'           => 'Razorpay',
                'payer_account_number' => '98711120003344',
                'payer_ifsc'           => 'CITI0000006',
            ],
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testMerchantFetchTpvsRouteViaBankingProductForViewOnlyRole' => [
        'request' => [
            'url'     => '/merchant/tpvs',
            'method'  => 'GET',
            'content' => [
                'balance_id'           => '10000000000000',
                'payer_name'           => 'Razorpay',
                'payer_account_number' => '98711120003344',
                'payer_ifsc'           => 'CITI0000006',
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 2,
                'items'  => [
                    [
                        'merchant_id'          => '10000000000000',
                        'balance_id'           => '10000000000000',
                        'status'               => 'approved',
                        'payer_name'           => 'Razorpay',
                        'payer_account_number' => '98711120003344',
                        'payer_ifsc'           => 'CITI0000006',
                        'created_by'           => 'OPS_A',
                        'bank_name'            => 'CITI Bank',
                        'type'                 => 'bank_account',
                        'is_active'            => true,
                    ],
                    [
                        'merchant_id'          => '10000000000000',
                        'balance_id'           => '10000000000000',
                        'status'               => 'rejected',
                        'payer_name'           => 'Razorpay',
                        'payer_account_number' => '8927398273',
                        'payer_ifsc'           => 'CITI0000006',
                        'created_by'           => 'OPS_A',
                        'bank_name'            => 'CITI Bank',
                        'type'                 => 'bank_account',
                        'is_active'            => false,
                        'remarks'              => 'Invalid docs'
                    ],
                ],
            ],
        ],
    ],

    'testMerchantTpvCreateRouteViaBankingProductForViewOnlyRole' => [
        'request' => [
            'url'     => '/merchant/tpv',
            'method'  => 'POST',
            'content' => [
                'balance_id'           => '10000000000000',
                'payer_name'           => 'Razorpay',
                'payer_account_number' => '98711120003344',
                'payer_ifsc'           => 'CITI0000006',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Authentication failed',
                ],
            ],
            'status_code' => 400,
        ],
    ],

    'testMerchantTpvCreateRouteViaBankingProductForFinanceL1Role' => [
        'request' => [
            'url'     => '/merchant/tpv',
            'method'  => 'POST',
            'content' => [
                'balance_id'           => '10000000000000',
                'payer_name'           => 'Razorpay',
                'payer_account_number' => '98711120003344',
                'payer_ifsc'           => 'CITI0000006',
            ],
        ],
        'response'  => [
            'content'     => [
                'merchant_id'          => '10000000000000',
                'balance_id'           => '10000000000000',
                'status'               => 'pending',
                'payer_name'           => 'Razorpay',
                'payer_account_number' => '98711120003344',
                'payer_ifsc'           => 'CITI0000006',
                'is_active'            => false,
                'type'                 => 'bank_account',
            ],
        ],
    ],

    'testMerchantTpvCreateRouteViaBankingProductForOwnerRole' => [
        'request' => [
            'url'     => '/merchant/tpv',
            'method'  => 'POST',
            'content' => [
                'balance_id'           => '10000000000000',
                'payer_name'           => 'Razorpay',
                'payer_account_number' => '98711120003344',
                'payer_ifsc'           => 'CITI0000006',
            ],
        ],
        'response'  => [
            'content'     => [
                'merchant_id'          => '10000000000000',
                'balance_id'           => '10000000000000',
                'status'               => 'pending',
                'payer_name'           => 'Razorpay',
                'payer_account_number' => '98711120003344',
                'payer_ifsc'           => 'CITI0000006',
                'is_active'            => false,
                'type'                 => 'bank_account',
            ],
        ],
    ],

    'testMerchantTpvCreateRouteViaBankingProductForOperationsRole' => [
        'request'  => [
            'url'     => '/merchant/tpv',
            'method'  => 'POST',
            'content' => [
                'balance_id'           => '10000000000000',
                'payer_name'           => 'Razorpay',
                'payer_account_number' => '98711120003344',
                'payer_ifsc'           => 'CITI0000006',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Authentication failed',
                ],
            ],
            'status_code' => 400,
        ],
    ],

    'testMerchantTpvCreateRouteViaBankingProductForAdminRole' => [
        'request'  => [
            'url'     => '/merchant/tpv',
            'method'  => 'POST',
            'content' => [
                'balance_id'           => '10000000000000',
                'payer_name'           => 'Razorpay',
                'payer_account_number' => '98711120003344',
                'payer_ifsc'           => 'CITI0000006',
            ],
        ],
        'response' => [
            'content'     => [
                'merchant_id'          => '10000000000000',
                'balance_id'           => '10000000000000',
                'status'               => 'pending',
                'payer_name'           => 'Razorpay',
                'payer_account_number' => '98711120003344',
                'payer_ifsc'           => 'CITI0000006',
                'is_active'            => false,
                'type'                 => 'bank_account',
            ],
        ],
    ],
];
