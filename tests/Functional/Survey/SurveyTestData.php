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
                'survey_ttl' => 30,
                'survey_url' => 'https://razorpay.typeform.com/to/IWuWQPm5#mid',
                'type' => 'nps_payouts'
            ],
        ],
        'response' => [
            'content' => [
                'name' => 'Test Survey',
                'description' => 'This is test survey',
                'survey_ttl' => '30',
                'survey_url' => 'https://razorpay.typeform.com/to/IWuWQPm5#mid',
                'type' => 'nps_payouts'
            ],
        ],
    ],

    'testCreateSurveyWithDuplicateType' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/survey',
            'content' => [
                'name' => 'Test Survey test',
                'description' => 'This is test survey',
                'survey_ttl' => 30,
                'type' => 'nps_payouts',
                'survey_url' => 'https://razorpay.typeform.com/to/IWuWQPm5#mid',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'description' => 'Survey with same type already exists',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_DUPLICATE_SURVEY_TYPE,
        ],
    ],

    'testUpdateSurveyTTL' => [
        'request'  => [
            'method'  => 'PATCH',
            'content' => [
                'survey_ttl' => 60
            ],
        ],
        'response' => [
            'content' => [
                'name' => 'Test Survey',
                'description' => 'This is test survey',
                'survey_ttl' => '60',
                'survey_url' => 'https://razorpay.typeform.com/to/IWuWQPm5#mid',
                'type' => 'nps_payouts'
            ],
        ],
    ],

    'testUpdateSurveyURL' => [
        'request'  => [
            'method'  => 'PATCH',
            'content' => [
                'survey_url' => 'https://razorpay.typeform.com/to/IWuWQPm5#mid'
            ],
        ],
        'response' => [
            'content' => [
                'name' => 'Test Survey',
                'description' => 'This is test survey',
                'survey_ttl' => 30,
                'survey_url' => 'https://razorpay.typeform.com/to/IWuWQPm5#mid',
                'type' => 'nps_payouts'
            ],
        ],
    ],

    'testUpdateSurveyName' => [
        'request'  => [
            'method'  => 'PATCH',
            'content' => [
                'name' => 'Test Survey updated'
            ],
        ],
        'response' => [
            'content' => [
                'name' => 'Test Survey updated',
                'description' => 'This is test survey',
                'survey_ttl' => 30,
                'survey_url' => 'https://razorpay.typeform.com/to/IWuWQPm5#mid',
                'type' => 'nps_payouts'
            ],
        ],
    ],

    'testUpdateSurveyDescription' => [
        'request'  => [
            'method'  => 'PATCH',
            'content' => [
                'description' => 'This is test survey updated'
            ],
        ],
        'response' => [
            'content' => [
                'name' => 'Test Survey',
                'description' => 'This is test survey updated',
                'survey_ttl' => 30,
                'survey_url' => 'https://razorpay.typeform.com/to/IWuWQPm5#mid',
                'type' => 'nps_payouts'
            ],
        ],
    ],

    'testUpdateSurveyWithInvalidId' => [
        'request'  => [
            'method'  => 'PATCH',
            'content' => [
                'description' => 'This is test survey updated'
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The id provided does not exist',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_ID,
        ],
    ],

    'testInvalidSurveyType' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/survey/scheduled/process',
            'content' => [
                'survey_type' => 'abc',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Invalid survey type',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_SURVEY_TYPE
        ],
    ],

    'testSurveyWithUserId' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/survey/scheduled/process',
            'content' => [
                'survey_type' => 'nps_payouts',
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
                'survey_type' => 'nps_payouts',
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
                'survey_type' => 'nps_payouts',
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
                'survey_type' => 'nps_payouts',
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
                'survey_type' => 'nps_payouts',
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
                'survey_type' => 'nps_payouts',
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
                'survey_type' => 'nps_payouts',
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
                'survey_type' => 'nps_payouts',
            ],
        ],
        'response' => [
            'content' => [
                'dispatched_cohort_count' => 1,
            ],
        ],
    ],

    'testPendingSurvey' => [
        'request'  => [
            'method'  => 'GET',
            'url'     => '/survey/pending?user_id=MerchantUser01',
        ],
        'response' => [
            'content' => [
                'id' => 'PLtIMZYR32kZiB',
                'survey_id' => 'GLuIMZYR32kZiB',
                'survey_email' => 'merchantuser01@razorpay.com',
                'attempts' => 1,
                'skip_in_app' => 0
            ],
        ],
    ],

    'testPendingSurveyWithSurveyAlreadyFilled' => [
        'request'  => [
            'method'  => 'GET',
            'url'     => '/survey/pending?user_id=MerchantUser01',
        ],
        'response' => [
            'content' => [

            ],
        ],
    ],

    'testPendingSurveyWithSurveyAlreadySkipped' => [
        'request'  => [
            'method'  => 'GET',
            'url'     => '/survey/pending?user_id=MerchantUser01',
        ],
        'response' => [
            'content' => [

            ],
        ],
    ],

    'testSkipInAppSurvey' => [
        'request'  => [
            'method'  => 'PATCH',
            'content' => [
                'skip_in_app' => true,
            ],
        ],
        'response' => [
            'content' => [
                'id' => 'PLtIMZYR32kZiB',
                'survey_id' => 'GLuIMZYR32kZiB',
                'survey_email' => 'merchantuser01@razorpay.com',
                'attempts' => 1,
                'skip_in_app' => '1'
            ],
        ],
    ],

    'testFailureSurveyTypeformWebhookConsumptionSecurity' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/survey/typeform/nps/webhook',
            'content' => [
            ],
        ],
        'response' => [
            'content'     => [
                'error' => [
                    'code'        => ErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Access Denied'
                ],
            ],
            'status_code' => 400,
        ],
    ],

    'testSuccessSurveyTypeformWebhookConsumptionWithoutTrackerId' => [
        'request'     => [
            'server'  => [
                'HTTP_TYPEFORM_SIGNATURE' => 'sha256=X6+H7ZHluBgqX31COwXi+VJfsmXI2TwGQk5JssE7KwY='
            ],
            'method'  => 'POST',
            'url'     => '/survey/typeform/nps/webhook',
            'content' => [
                "event_id"      => "IWuWQPm5",
                "event_type"    => "form_response",
                "form_response" => [
                    "form_id"      => "lT4Z3j",
                    "token"        => "a3a12ec67a1365927098a606107fac15",
                    "submitted_at" => "2018-01-18T18:17:02Z",
                    "landed_at"    => "2018-01-18T18:07:02Z",
                    "hidden"       => [
                        "mid" => "EV7j5qM0qca1U3",
                        "uid" => "MerchantUser01",
                    ],
                    "calculated"   => [
                        "score" => 9
                    ],
                    "definition"   => [
                        "id"     => "lT4Z3j",
                        "title"  => "Webhooks example",
                        "fields" => [
                            [
                                "id"                        => "PNe8ZKBK8C2Q",
                                "title"                     => "Which feature you like the most in razorpay",
                                "type"                      => "picture_choice",
                                "ref"                       => "readable_ref_picture_choice",
                                "allow_multiple_selections" => true,
                                "allow_other_choice"        => false
                            ],
                            [
                                "id"                        => "Q7M2XAwY04dW",
                                "title"                     => "On a scale of 1 to 5, what rating would you give for our onboarding experience",
                                "type"                      => "number",
                                "ref"                       => "readable_ref_number1",
                                "allow_multiple_selections" => false,
                                "allow_other_choice"        => false
                            ],
                        ]
                    ],
                    "answers"      => [
                        [
                            "type"    => "choices",
                            "choices" => [
                                "labels" => [
                                    "Payout",
                                    "FTS"
                                ]
                            ],
                            "field"   => [
                                "id"   => "PNe8ZKBK8C2Q",
                                "type" => "picture_choice"
                            ]
                        ],
                        [
                            "type"   => "number",
                            "number" => 5,
                            "field"  => [
                                "id"   => "Q7M2XAwY04dW",
                                "type" => "number"
                            ]
                        ],
                    ]
                ]
            ]
        ],
        'response'    => [
            'content' => [
                'success' => true,
                'survey_response_saved' => true
            ],
        ],
        'status_code' => 200,
    ],

    'testSuccessSurveyTypeformWebhookConsumptionWithTrackerId' => [
        'request'     => [
            'server'  => [
                'HTTP_TYPEFORM_SIGNATURE' => 'sha256=X6+H7ZHluBgqX31COwXi+VJfsmXI2TwGQk5JssE7KwY='
            ],
            'method'  => 'POST',
            'url'     => '/survey/typeform/nps/webhook',
            'content' => [
                "event_id"      => "IWuWQPm5",
                "event_type"    => "form_response",
                "form_response" => [
                    "form_id"      => "lT4Z3j",
                    "token"        => "a3a12ec67a1365927098a606107fac15",
                    "submitted_at" => "2018-01-18T18:17:02Z",
                    "landed_at"    => "2018-01-18T18:07:02Z",
                    "hidden"       => [
                        "mid" => "EV7j5qM0qca1U3",
                        "uid" => "MerchantUser01",
                    ],
                    "calculated"   => [
                        "score" => 9
                    ],
                    "definition"   => [
                        "id"     => "lT4Z3j",
                        "title"  => "Webhooks example",
                        "fields" => [
                            [
                                "id"                        => "PNe8ZKBK8C2Q",
                                "title"                     => "Which feature you like the most in razorpay",
                                "type"                      => "picture_choice",
                                "ref"                       => "readable_ref_picture_choice",
                                "allow_multiple_selections" => true,
                                "allow_other_choice"        => false
                            ],
                            [
                                "id"                        => "Q7M2XAwY04dW",
                                "title"                     => "On a scale of 1 to 5, what rating would you give for our onboarding experience",
                                "type"                      => "number",
                                "ref"                       => "readable_ref_number1",
                                "allow_multiple_selections" => false,
                                "allow_other_choice"        => false
                            ],
                        ]
                    ],
                    "answers"      => [
                        [
                            "type"    => "choices",
                            "choices" => [
                                "labels" => [
                                    "Payout",
                                    "FTS"
                                ]
                            ],
                            "field"   => [
                                "id"   => "PNe8ZKBK8C2Q",
                                "type" => "picture_choice"
                            ]
                        ],
                        [
                            "type"   => "number",
                            "number" => 5,
                            "field"  => [
                                "id"   => "Q7M2XAwY04dW",
                                "type" => "number"
                            ]
                        ],
                    ]
                ]
            ]
        ],
        'response'    => [
            'content' => [
                'success' => true,
                'survey_response_saved' => true
            ],
        ],
        'status_code' => 200,
    ],

    'testSuccessSurveyTypeformWebhookWithSurveyAlreadyFilledBefore' => [
        'request'     => [
            'server'  => [
                'HTTP_TYPEFORM_SIGNATURE' => 'sha256=X6+H7ZHluBgqX31COwXi+VJfsmXI2TwGQk5JssE7KwY='
            ],
            'method'  => 'POST',
            'url'     => '/survey/typeform/nps/webhook',
            'content' => [
                "event_id"      => "IWuWQPm5",
                "event_type"    => "form_response",
                "form_response" => [
                    "form_id"      => "lT4Z3j",
                    "token"        => "a3a12ec67a1365927098a606107fac15",
                    "submitted_at" => "2018-01-18T18:17:02Z",
                    "landed_at"    => "2018-01-18T18:07:02Z",
                    "hidden"       => [
                        "mid" => "EV7j5qM0qca1U3",
                        "uid" => "MerchantUser01",
                    ],
                    "calculated"   => [
                        "score" => 9
                    ],
                    "definition"   => [
                        "id"     => "lT4Z3j",
                        "title"  => "Webhooks example",
                        "fields" => [
                            [
                                "id"                        => "PNe8ZKBK8C2Q",
                                "title"                     => "Which feature you like the most in razorpay",
                                "type"                      => "picture_choice",
                                "ref"                       => "readable_ref_picture_choice",
                                "allow_multiple_selections" => true,
                                "allow_other_choice"        => false
                            ],
                            [
                                "id"                        => "Q7M2XAwY04dW",
                                "title"                     => "On a scale of 1 to 5, what rating would you give for our onboarding experience",
                                "type"                      => "number",
                                "ref"                       => "readable_ref_number1",
                                "allow_multiple_selections" => false,
                                "allow_other_choice"        => false
                            ],
                        ]
                    ],
                    "answers"      => [
                        [
                            "type"    => "choices",
                            "choices" => [
                                "labels" => [
                                    "Payout",
                                    "FTS"
                                ]
                            ],
                            "field"   => [
                                "id"   => "PNe8ZKBK8C2Q",
                                "type" => "picture_choice"
                            ]
                        ],
                        [
                            "type"   => "number",
                            "number" => 5,
                            "field"  => [
                                "id"   => "Q7M2XAwY04dW",
                                "type" => "number"
                            ]
                        ],
                    ]
                ]
            ]
        ],
        'response'    => [
            'content' => [
                'success' => true,
                'survey_response_saved' => false
            ],
        ],
        'status_code' => 200,
    ],

    'testSurveyOnCAOnboarding' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/survey/scheduled/process',
            'content' => [
                'survey_type' => 'nps_csat',
            ],
        ],
        'response' => [
            'content' => [
                'dispatched_cohort_count' => 1,
            ],
        ],
    ],

    'testSurveyOnCAOnboardedBeneficiaryEmail' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/survey/scheduled/process',
            'content' => [
                'survey_type' => 'nps_csat',
            ],
        ],
        'response' => [
            'content' => [
                'dispatched_cohort_count' => 1,
            ],
        ],
    ],

    'testSurveyOnAccountArchived' =>  [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/survey/scheduled/process',
            'content' => [
                'survey_type' => 'nps_csat',
            ],
        ],
        'response' => [
            'content' => [
                'dispatched_cohort_count' => 1,
            ],
        ],
    ],

    'testSurveyOnCAOnboardingMerchantPocAndBeneficiary' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/survey/scheduled/process',
            'content' => [
                'survey_type' => 'nps_csat',
            ],
        ],
        'response' => [
            'content' => [
                'dispatched_cohort_count' => 1,
            ],
        ],
    ],

    'testSurveyOnCAWithAcrossSurveyCheckFailing' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/survey/scheduled/process',
            'content' => [
                'survey_type' => 'nps_csat',
            ],
        ],
        'response' => [
            'content' => [
                'dispatched_cohort_count' => 1,
            ],
        ],
    ],

    'testPrecedenceForCSAT' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/survey/scheduled/process',
            'content' => [
                'survey_type' => 'nps_csat',
            ],
        ],
        'response' => [
            'content' => [
                'dispatched_cohort_count' => 1,
            ],
        ],
    ],

    'testPrecedenceForNPSPayouts' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/survey/scheduled/process',
            'content' => [
                'survey_type' => 'nps_payouts',
            ],
        ],
        'response' => [
            'content' => [
                'dispatched_cohort_count' => 3,
            ],
        ],
    ],

    'testPrecedenceForActiveCAPayouts' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/survey/scheduled/process',
            'content' => [
                'survey_type' => 'nps_active_ca',
            ],
        ],
        'response' => [
            'content' => [
                'dispatched_cohort_count' => 2,
            ],
        ],
    ],
];
