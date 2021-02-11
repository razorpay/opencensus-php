<?php

namespace RZP\Tests\Functional\BankingAccountTpv;

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
                        'payer_account_number' => '98711120003344',
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
            'url'     => '/admin/tpv/edit',
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

    'testAdminEditTpvInvalidAccountNumber' => [
        'request'   => [
            'url'     => '/admin/tpv/edit',
            'method'  => 'patch',
            'content' => [
                'merchant_id'          => '10000000000000',
                'balance_id'           => '10000000000000',
                'payer_account_number' => '98711120003',
                'status'               => Status::REJECTED,
                'remarks'              => 'Morphed docs',
                'payer_ifsc'           => 'CITI0000006',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_TPV_NOT_EXISTS,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_TPV_NOT_EXISTS,
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

];
