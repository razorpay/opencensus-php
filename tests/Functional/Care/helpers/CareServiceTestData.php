<?php

use RZP\Error\ErrorCode;

return [
    'testInternalMerchantFetch' => [
        'request'  => [
            'method' => 'GET',
            'url'    => '/internal/merchants/10000000000000',
        ],
        'response' => [
            'content' => [
                'merchant' => [
                    'id'         => '10000000000000',
                    'activated'  => false,
                    'hold_funds' => false,
                ],
            ],
        ],
    ],

    'testProxy' => [
        'request'  => [
            'method' => 'POST',
        ],
        'response' => [
            'content' => [

            ],
        ],
    ],

    'testInternalMerchantGetFirstSubmissionDate' => [
        'request'  => [
            'method' => 'GET',
            'url'    => '/internal/merchants/10000000000000/submission_date',
        ],
        'response' => [
            'content' => [
                'first_l2_submission_timestamp' => 1539543931
            ],
        ],
    ],

    'testInternalMerchantGetFirstSubmissionDateWithDifferentStatus' => [
        'request'  => [
            'method' => 'GET',
            'url'    => '/internal/merchants/10000000000000/submission_date',
        ],
        'response' => [
            'content' => [
                'first_l2_submission_timestamp' => 1539543929
            ],
        ],
    ],

    'testDashboardProxyInvalidRoute' => [
        'request'   => [
            'method' => 'POST',
            'url'    => '/care_service/merchant/twirp/random',
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => 'BAD_REQUEST_ERROR',
                    'description' => 'The requested URL was not found on the server.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => \RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_URL_NOT_FOUND,
        ],
    ],

    'testProxy400Exception' => [
        'request'   => [
            'method' => 'POST',
            'url'    => '/care_service/merchant/twirp/rzp.care.callback.v1.CallbackService/CheckEligibility',
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => 'BAD_REQUEST_ERROR',
                    'description' => 'error message',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => \RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_ERROR,
        ],
    ],

    'testProxy500Exception' => [
        'request'   => [
            'method' => 'POST',
            'url'    => '/care_service/merchant/twirp/rzp.care.callback.v1.CallbackService/CheckEligibility',
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => 'SERVER_ERROR',
                    'description' => 'We are facing some trouble completing your request at the moment. Please try again shortly.',
                ],
            ],
            'status_code' => 500,
        ],
        'exception' => [
            'class'               => \RZP\Exception\IntegrationException::class,
            'internal_error_code' => ErrorCode::SERVER_ERROR,
        ],
    ],

    'testInvalidPermission' => [
        'request'   => [
            'method' => 'POST',
            'url'    => '/care_service/admin/twirp/rzp.care.admin.v1.CallbackConfigService/editWeekSlotConfig',
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => 'BAD_REQUEST_ERROR',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_ACCESS_DENIED
        ],
    ],

    'testInternalMerchantGetRejectionReasons' => [
        'request'  => [
            'method' => 'GET',
            'url'    => '/internal/merchants/10000000000000/rejection_reasons',
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 1,
                'items'  => [
                    [
                        'reason_type'        => 'rejection',
                        'reason_category'    => 'risk_related_rejections',
                        'reason_description' => 'Merchant rejected based on Risk team\'s remarks',
                    ],
                ],
            ],
        ],
    ],
];
