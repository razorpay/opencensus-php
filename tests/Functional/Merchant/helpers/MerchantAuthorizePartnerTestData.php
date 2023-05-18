<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;

return [
    'testGetMerchantAuthorizationStatusWhenMappedToPartner' => [
        'request'  => [
            'content' => [
                'partner_id'    => 'DefaultPartner'
            ],
            'url'     => '/merchant/10000000000111/authorize/partner',
            'method'  => 'GET',
        ],
        'response' => [
            'content' => [
                'partner_access'                   => true,
                'partner_name'                     => 'Amazon Inc',
                'partner_type'                     => 'aggregator'
            ],
        ],
    ],

    'testGetMerchantAuthorizationStatusWhenNotMappedToPartner' => [
        'request'  => [
            'content' => [
                'partner_id'    => 'DefaultPartner'
            ],
            'url'     => '/merchant/10000000000000/authorize/partner',
            'method'  => 'GET',
        ],
        'response' => [
            'content' => [
                'partner_access'                   => false,
                'partner_name'                     => 'Amazon Inc',
                'partner_type'                     => 'aggregator'
            ],
        ],
    ],

    'testGetMerchantAuthorizationStatusWhenInvalidPartnerIdProvided' => [
        'request'  => [
            'content' => [
                'partner_id'    => 'DefaultPartner'
            ],
            'url'     => '/merchant/10000000000000/authorize/partner',
            'method'  => 'GET',
        ],
        'response' => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The id provided does not exist',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_ID,
        ],
    ],

    'testGetMerchantAuthorizationStatusWhenInvalidMerchantIdProvided' => [
        'request'  => [
            'content' => [
                'partner_id'    => 'DefaultPartner'
            ],
            'url'     => '/merchant/10000000000111/authorize/partner',
            'method'  => 'GET',
        ],
        'response' => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The id provided does not exist',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_ID,
        ],
    ],

    'testGetMerchantAuthorizationStatusWhenPartnerIsNotAggregator' => [
        'request'  => [
            'content' => [
                'partner_id'    => 'DefaultPartner'
            ],
            'url'     => '/merchant/10000000000111/authorize/partner',
            'method'  => 'GET',
        ],
        'response' => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Invalid partner action',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_PARTNER_ACTION,
        ],
    ],

    'testGetMerchantAuthorizationStatusWhenPartnerIdProvidedIsMerchant' => [
        'request'  => [
            'content' => [
                'partner_id'    => '10000000000000'
            ],
            'url'     => '/merchant/10000000000111/authorize/partner',
            'method'  => 'GET',
        ],
        'response' => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Merchant is not a partner',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_MERCHANT_IS_NOT_PARTNER,
        ],
    ],

    'testSaveMerchantAuthorization' => [
        'request'  => [
            'content' => [
                'partner_id'    => 'DefaultPartner'
            ],
            'url'     => '/merchant/10000000000000/authorize/partner',
            'method'  => 'POST',
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testSaveMerchantAuthorizationWhenMerchantMappedToPartner' => [
        'request'  => [
            'content' => [
                'partner_id'    => 'DefaultPartner'
            ],
            'url'     => '/merchant/10000000000000/authorize/partner',
            'method'  => 'POST',
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testSaveMerchantAuthorizationWhenInvalidPartnerIdProvided' => [
        'request'  => [
            'content' => [
                'partner_id'    => 'DefaultPartner'
            ],
            'url'     => '/merchant/10000000000000/authorize/partner',
            'method'  => 'POST',
        ],
        'response' => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The id provided does not exist',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_ID,
        ],
    ],

    'testSaveMerchantAuthorizationWhenInvalidMerchantIdProvided' => [
        'request'  => [
            'content' => [
                'partner_id'    => 'DefaultPartner'
            ],
            'url'     => '/merchant/10000000000111/authorize/partner',
            'method'  => 'POST',
        ],
        'response' => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The id provided does not exist',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_ID,
        ],
    ],

    'testSaveMerchantAuthorizationWhenPartnerIsNotAggregator' => [
        'request'  => [
            'content' => [
                'partner_id'    => '10000000000111'
            ],
            'url'     => '/merchant/10000000000000/authorize/partner',
            'method'  => 'POST',
        ],
        'response' => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Merchant is not a partner',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_MERCHANT_IS_NOT_PARTNER,
        ],
    ],

    'testSaveMerchantAuthorizationWhenMerchantConsentIsAlreadyPresent' => [
        'request'  => [
            'content' => [
                'partner_id'    => 'DefaultPartner'
            ],
            'url'     => '/merchant/10000000000111/authorize/partner',
            'method'  => 'POST',
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testSaveMerchantAuthorizationWhenMerchantConsentIsPresentForAnotherPartner' => [
        'request'  => [
            'content' => [
                'partner_id'    => 'DefaultPartner'
            ],
            'url'     => '/merchant/10000000000000/authorize/partner',
            'method'  => 'POST',
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],
];
