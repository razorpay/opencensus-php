<?php

use RZP\Error\ErrorCode;

return [
    'testCreateMerchantReferral' => [
        'request'  => [
            'content' => [
            ],
            'url'     => '/merchant/referral',
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [
                'merchant_id' => '10000000000000',
            ],
        ],
    ],

    'testFetchMerchantReferral' => [
        'request'  => [
            'url'    => '/merchant/referral',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                'merchant_id' => '10000000000000',
            ],
        ],
    ],

    'testFetchMerchantReferralBatch' => [
        'request'  => [
            'url'    => '/partner_referral/bulk',
            'method' => 'POST',
            'content' => [
                'merchant_id' => '10000000000000',
                'product'     => 'Primary'
            ]
        ],
        'response' => [
            'content' => [
                'merchant_id' => '10000000000000',
                'Status'      => 'Success'
            ],
        ],
    ],

    'testFetchMerchantReferralBatchFailureReferralNotFound' => [
        'request'  => [
            'url'    => '/partner_referral/bulk',
            'method' => 'POST',
            'content' => [
                'merchant_id' => '10000000000000',
                'product'     => 'primary'
            ]
        ],
        'response' => [
            'content' => [
                'merchant_id'       => '10000000000000',
                'Error Code'        => ErrorCode::BAD_REQUEST_ERROR,
                'Error Description' => 'Partner referral does not exist',
                'Status'            => 'Failure',
            ],
        ],
    ],

    'testFetchMerchantReferralBatchFailureMerchantNotFound' => [
        'request'  => [
            'url'    => '/partner_referral/bulk',
            'method' => 'POST',
            'content' => [
                'merchant_id' => '10000000000098',
                'product'     => 'primary'
            ]
        ],
        'response' => [
            'content' => [
                'merchant_id'       => '10000000000098',
                'Error Code'        => ErrorCode::BAD_REQUEST_ERROR,
                'Error Description' => 'The id provided does not exist',
                'Status'            => 'Failure',
            ],
        ],
    ],

    'testFetchMerchantReferralBatchFailureInvalidId' => [
        'request'  => [
            'url'    => '/partner_referral/bulk',
            'method' => 'POST',
            'content' => [
                'merchant_id' => '10000',
                'product'     => 'primary'
            ]
        ],
        'response' => [
            'content' => [
                'merchant_id'       => '10000',
                'Error Code'        => ErrorCode::BAD_REQUEST_ERROR,
                'Error Description' => '10000 is not a valid id',
                'Status'            => 'Failure',
            ],
        ],
    ],

    'testFetchMerchantReferralBatchFailureMerchantNotPartner' => [
        'request'  => [
            'url'    => '/partner_referral/bulk',
            'method' => 'POST',
            'content' => [
                'merchant_id' => '10000000000000',
                'product'     => 'primary'
            ]
        ],
        'response' => [
            'content' => [
                'merchant_id'         => '10000000000000',
                'Error Code'          => ErrorCode::BAD_REQUEST_ERROR,
                'Error Description'   => 'Merchant is not a partner',
                'Status'              => 'Failure',
            ],
        ],
    ],

    'testCreateReferralNonResellerPartner' => [
        'request'   => [
            'url'    => '/merchant/referral',
            'method' => 'POST'
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code' => ErrorCode::BAD_REQUEST_ERROR,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_MERCHANT_NOT_UNDER_PARTNER,
        ],
    ],

    'testCreateOrFetchReferral' => [
        'request'   => [
            'url'    => '/merchant/referral',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'merchant_id' => '10000000000000',
                'product'     => 'primary',
                'referrals'   =>  [
                    'primary' => [
                        'merchant_id' => '10000000000000'
                    ],
                    'banking' => [
                        'merchant_id' => '10000000000000'
                    ],
                ]
            ],
        ],
    ],

    'testRegenerateReferralLinksWithInvalidMerchant' => [
        'request'   => [
            'url'     => '/merchant/referral/regenerate',
            'method'  => 'POST',
            'content' => [
                'partner_ids' => [
                    '10000000000000'
                ],
            ]
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code' => ErrorCode::BAD_REQUEST_ERROR,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_ERROR,
        ],
    ],

    'testCreateOrFetchMerchantReferralPartnerEligibleForCapital' => [
        'request'  => [
            'url'    => '/merchant/referral',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'merchant_id' => '10000000000000',
            ],
        ],
    ],

    'testCreateOrFetchMerchantReferralPartnerNotEligibleForCapital' => [
        'request'  => [
            'url'    => '/merchant/referral',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'merchant_id' => '10000000000000',
            ],
        ],
    ],
];
