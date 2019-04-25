<?php

namespace RZP\Tests\Functional\Merchant;

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testGetCommissionsEmpty' => [
        'request'  => [
            'url'    => '/commissions',
            'method' => 'GET',
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testGetCommissions' => [
        'request'  => [
            'url'    => '/commissions',
            'method' => 'GET',
        ],
        'response' => [
            // response assertions are in function
            'content' => [],
        ],
    ],

    'testGetCommissionById' => [
        'request'  => [
            'url'    => '/commissions/{id}',
            'method' => 'GET',
        ],
        'response' => [
            'content' => [
                'entity'      => 'commission',
                'status'      => 'created',
                'currency'    => 'INR',
                'partner_id'  => 'DefaultPartner',
                'source_type' => 'payment',
                'merchant'    => [
                    'id' => 'submerchantNum',
                ],
                'source'      => [
                    'entity' => 'payment',
                    'status' => 'captured',
                ],
            ],
        ],
    ],

    'testGettingCommissionsByFilters' => [
        'request'  => [
            'url'     => '/commissions',
            'method'  => 'GET',
            'content' => [],
        ],
        'response' => [
            // response assertions are in function
            'content' => [],
        ],
    ],

    'testGettingCommissionsForAdminByFilters' => [
        'request'  => [
            'url'    => '/admin/commission',
            'method' => 'GET',
        ],
        'response' => [
            // response assertions are in function
            'content' => [],
        ],
    ],

    'testGettingCommissionsForResellers' => [
        'request'  => [
            'url'     => '/commissions',
            'method'  => 'GET',
            'content' => [],
        ],
        'response' => [
            'content'   => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_ACCESS_NOT_ALLOWED_FOR_RESELLER,
                ]
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_ACCESS_NOT_ALLOWED_FOR_RESELLER,
        ],
    ],
];
