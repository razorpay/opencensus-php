<?php

use RZP\Gateway\Hdfc;
use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [

    'testGetMerchantDetails' => [
        'request' => [
            'url' => '/merchant/activation',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                'verification' => [
                    'status' => 'disabled',
                    'disabled_reason' => 'required_fields',
                ],
                'can_submit' => false,
            ],
        ],
    ],

    'testUpdateIfscCode' => [
        'request' => [
            'content' => [
                'bank_branch_ifsc' => 'ICIC0000002'
            ],
            'url' => '/merchant/activation',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'bank_branch_ifsc' => 'ICIC0000002',
                'verification' => [
                    'status' => 'disabled',
                    'disabled_reason' => 'required_fields',
                ],
                'can_submit' => false,
            ],
        ],
    ],

    'testSubmit' => [
        'request' => [
            'content' => [
                'submit' => true
            ],
            'url' => '/merchant/activation',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'submitted' => true,
                'verification' => [
                    'status' => 'pending'
                ],
                'can_submit' => true,
            ],
        ],
    ],

    'testSubmitWithInvalidFields' => [
        'request' => [
            'content' => [
                'submit' => true
            ],
            'url' => '/merchant/activation',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'verification' => [
                    'status' => 'disabled',
                    'disabled_reason' => 'required_fields',
                ],
                'can_submit' => false,
            ],
        ],
    ],

    'testUpdateIfscCodeWithFailure' => [
        'request' => [
            'content' => [
                'bank_branch_ifsc' => 'ICIC000000'
            ],
            'url' => '/merchant/activation',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Invalid IFSC Code',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testUpdateEmail' => [
        'request' => [
            'content' => [
                'transaction_report_email' => 'a.b@c.com,a.c@d.com'
            ],
            'url' => '/merchant/activation',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'transaction_report_email' => 'a.b@c.com,a.c@d.com',
                'verification' => [
                    'status' => 'disabled',
                    'disabled_reason' => 'required_fields',
                ],
                'can_submit' => false,
            ],
        ],
    ],

    'testUpdateEmails' => [
        'request' => [
            'content' => [
                'transaction_report_email' => 'a.b@c.com,a.c'
            ],
            'url' => '/merchant/activation',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The provided transaction report email is invalid: a.c',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testUpdateEmailWithFailure' => [
        'request' => [
            'content' => [
                'transaction_report_email' => 'a.b'
            ],
            'url' => '/merchant/activation',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The provided transaction report email is invalid: a.b',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testUpdateDetailForLockedMerchant' => [
        'request' => [
            'content' => [
                'bank_branch_ifsc' => 'ICIC0000001'
            ],
            'url' => '/merchant/activation',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Activation form has been locked for editing by admin.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_MERCHANT_DETAIL_ALREADY_LOCKED,
        ],
    ],

    'testLockMerchant' => [
        'request' => [
            'content' => [
                'locked' => true
            ],
            'method' => 'PUT'
        ],
        'response' => [
            'content' => [
                'locked' => true,
                'verification' => [
                    'status' => 'disabled',
                    'disabled_reason' => 'required_fields',
                ],
                'can_submit' => false,
            ],
        ],
    ],

    'testCommentMerchant' => [
        'request' => [
            'content' => [
                'comment' => 'true'
            ],
            'method' => 'PUT'
        ],
        'response' => [
            'content' => [
                'verification' => [
                    'status' => 'disabled',
                    'disabled_reason' => 'required_fields',
                ],
                'can_submit' => false,
            ],
        ],
    ],

    'testCommentForLockedMerchant' => [
        'request' => [
            'content' => [
                'comment' => 'true'
            ],
            'url' => '/merchant/activation/lock',
            'method' => 'PUT'
        ],
        'response' => [
            'content' => [
                'verification' => [
                    'status' => 'disabled',
                    'disabled_reason' => 'required_fields',
                ],
                'can_submit' => false,
            ],
        ],
    ],

    'testCommentMerchantWithNoMerchantDetail' => [
        'request' => [
            'content' => [
                'comment' => 'true'
            ],
            'url' => '/merchant/activation/lock',
            'method' => 'PUT'
        ],
        'response' => [
            'content' => [
                'verification' => [
                    'status' => 'disabled',
                    'disabled_reason' => 'required_fields',
                ],
                'can_submit' => false,
            ],
        ],
    ],

    'testUnlockMerchant' => [
        'request' => [
            'content' => [
                'locked' => 0
            ],
            'url' => '/merchant/activation/lock',
            'method' => 'PUT'
        ],
        'response' => [
            'content' => [
                'locked' => false,
                'verification' => [
                    'status' => 'disabled',
                    'disabled_reason' => 'required_fields',
                ],
                'can_submit' => false,
            ],
        ],
    ],

    'testUnlockMerchant2' => [
        'request' => [
            'content' => [
                'locked' => 0
            ],
            'url' => '/merchant/activation/lock',
            'method' => 'PUT'
        ],
        'response' => [
            'content' => [
                'locked' => false,
                'verification' => [
                    'status' => 'disabled',
                    'disabled_reason' => 'required_fields',
                ],
                'can_submit' => false,
            ],
        ],
    ],

    'testCreateMerchantDetailIfNotExist' => [
        'request' => [
            'content' => [
                'bank_branch_ifsc' => 'ICIC0000002',
            ],
            'url' => '/merchant/activation',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'bank_branch_ifsc' => 'ICIC0000002',
                'verification' => [
                    'status' => 'disabled',
                    'disabled_reason' => 'required_fields',
                ],
                'can_submit' => false,
            ],
        ],
    ],

    'testZohoMerchantHeaders' => [
        'response' => [
            'content' => [
                'error' => [
                    'code'        => ErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Payment failed',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],
    'testMerchantDetailsFetch' => [
        'request' => [
            'url'       => '/merchants/details',
            'method'    => 'GET',
            'content'   => [],
            'server' => [
                'HTTP_' . \RZP\Http\BasicAuth\BasicAuth::ACCOUNT_HEADER_KEY => '10000000000002',
            ],
        ],
        'response' => [
            'content' => [
                'id'                        => '10000000000002',
                'entity'                    => 'merchant',
                'activated'                 => false,
                'live'                      => false,
                'methods'                   => [
                    'merchant_id'   => '10000000000002',
                    'amex'          => false,
                ],

                'convert_currency'          => null,
                'org_id'                    => \RZP\Tests\Functional\Fixtures\Entity\Org::RZP_ORG,
                'groups'                    => [],
                'admins'                    => [],
                'transaction_report_email'  => [],
                'tags'                      => [],
                'confirmed'                 => false,
                'logo_url'                  => null,
                'merchant_details'          => [
                    'contact_email'         => 'razorpay@razorpay.com',
                    'gstin'                 => null,
                    'p_gstin'               => null,
                    'activation_progress'   => 0,
                    'can_submit'            => false,
                    'steps_finished'        => [],
                    'activated'             => 0,
                    'verification'          => [
                        'status'                => 'disabled',
                        'disabled_reason'       => 'required_fields',
                        'activation_progress'   => 3,
                    ],
                ],
                'auto_capture_late_auth'    => false,
                'fee_bearer'                => 'platform',
                'fee_model'                 => 'prepaid',
                'international'             => true,
                'max_payment_amount'        => 50000000,
                'suspended_at'              => null,
            ],
        ],
    ],
];
