<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;

return [
    'testCreateSurvey' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/survey',
            'content' => [
                'name' => 'Test Survey',
                'description' => 'This is test survey',
                'survey_ttl' => 30
            ],
        ],
        'response' => [
            'content' => [
                'name' => 'Test Survey',
                'description' => 'This is test survey',
                'survey_ttl' => '30'
            ],
        ],
    ],

    'testInvalidSurveyId' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/survey/scheduled/process',
            'content' => [
                'survey_type' => 'nps_razorpay_x',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_ID
        ],
    ],

    'testSurveyWithUserId' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/survey/scheduled/process',
            'content' => [
                'survey_type' => 'nps_razorpay_x',
            ],
        ],
        'response' => [
            'content' => [
                'dispatched_cohort_count' => 2,
            ],
        ],
    ],

    'testSurveyWithNoUserId' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/survey/scheduled/process',
            'content' => [
                'survey_type' => 'nps_razorpay_x',
            ],
        ],
        'response' => [
            'content' => [
                'dispatched_cohort_count' => 1,
            ],
        ],
    ],

    'testSurveyWithSameMerchantAndUserId' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/survey/scheduled/process',
            'content' => [
                'survey_type' => 'nps_razorpay_x',
            ],
        ],
        'response' => [
            'content' => [
                'dispatched_cohort_count' => 1,
            ],
        ],
    ],

    'testSurveyWithSameMerchantAndDifferentUserId' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/survey/scheduled/process',
            'content' => [
                'survey_type' => 'nps_razorpay_x',
            ],
        ],
        'response' => [
            'content' => [
                'dispatched_cohort_count' => 2,
            ],
        ],
    ],

    'testSurveyWithDifferentMerchantAndSameUserId' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/survey/scheduled/process',
            'content' => [
                'survey_type' => 'nps_razorpay_x',
            ],
        ],
        'response' => [
            'content' => [
                'dispatched_cohort_count' => 2,
            ],
        ],
    ],

    'testSurveyWithEmailAlreadySent' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/survey/scheduled/process',
            'content' => [
                'survey_type' => 'nps_razorpay_x',
            ],
        ],
        'response' => [
            'content' => [
                'dispatched_cohort_count' => 1,
            ],
        ],
    ],

    'testSurveyAfterSurveyTTL' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/survey/scheduled/process',
            'content' => [
                'survey_type' => 'nps_razorpay_x',
            ],
        ],
        'response' => [
            'content' => [
                'dispatched_cohort_count' => 1,
            ],
        ],
    ],

    'testSurveywithExternalUserId' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/survey/scheduled/process',
            'content' => [
                'survey_type' => 'nps_razorpay_x',
            ],
        ],
        'response' => [
            'content' => [
                'dispatched_cohort_count' => 1,
            ],
        ],
    ],
];
