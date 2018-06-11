<?php

namespace RZP\Tests\Functional\Merchant\Partner;

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [

    'testMarkingMerchantAsPartner' => [
        'request'   => [
            'url'     => '/merchant/requests',
            'method'  => 'POST',
            'content' => [
                'name' => 'reseller',
                'type' => 'partner_activation',
            ],
        ],
        'response' => [
            'content' => [
                'status'      => 'under_review',
                'name'        => 'reseller',
                'merchant'    => [
                    'id' => '10000000000000',
                ],
                'states'      => [
                    'entity' => 'collection',
                    'items'  => [
                        [
                            'name' => 'under_review',
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testMarkingMerchantAsPartnerInvalidType' => [
        'request'   => [
            'url'     => '/merchant/requests',
            'method'  => 'POST',
            'content' => [
                // name = marketplace because it's a valid merchant request name but an invalid partner type
                'name' => 'marketplace',
                'type' => 'partner_activation',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_INVALID_PARTNER_NAME,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testMerchantMarksSelfAsPartner' => [
        'request'   => [
            'url'     => '/merchant/requests',
            'method'  => 'POST',
            'content' => [
                'name' => 'reseller',
                'type' => 'partner_activation',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_MERCHANT_ACTION_NOT_SUPPORTED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testMerchantUnmarksSelfAsPartner' => [
        'request'   => [
            'url'     => '/merchant/requests',
            'method'  => 'POST',
            'content' => [
                'name' => 'reseller',
                'type' => 'partner_activation',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_MERCHANT_ACTION_NOT_SUPPORTED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testApprovingMarkAsPartnerMerchantRequest' => [
        'request'   => [
            'url'     => '/merchant/requests/100000RandomId',
            'method'  => 'PATCH',
            'content' => [
                'status' => 'activated',
            ],
        ],
        'response'   => [
            'content' => [
                'status' => 'activated',
            ],
        ],
    ],

    'testApprovingUnmarkAsPartnerMerchantRequest' => [
        'request'   => [
            'url'     => '/merchant/requests/100000RandomId',
            'method'  => 'PATCH',
            'content' => [
                'status' => 'activated',
            ],
        ],
        'response'   => [
            'content' => [
                'status' => 'activated',
            ],
        ],
    ],

    'testUnmarkingMerchantAsPartner' => [
        'request'   => [
            'url'     => '/merchant/requests',
            'method'  => 'POST',
            'content' => [
                'name' => 'reseller',
                'type' => 'partner_deactivation',
            ],
        ],
        'response' => [
            'content' => [
                'status'      => 'under_review',
                'name'        => 'reseller',
                'merchant'    => [
                    'id' => '10000000000000',
                ],
                'states'      => [
                    'entity' => 'collection',
                    'items'  => [
                        [
                            'name' => 'under_review',
                        ],
                    ],
                ],
            ],
        ],
    ],
];

