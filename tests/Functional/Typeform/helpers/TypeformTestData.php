<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;

return

    [
        'testFailureTypeformWebhookConsumptionSecurity' => [
            'request'  => [
                'method'  => 'POST',
                'url'     => '/typeform/webhook_consumption',
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

        'testSuccessTypeformWebhookConsumptionSecurity' => [
            'request'     => [
                'server'  => [
                    'HTTP_TYPEFORM_SIGNATURE' => 'sha256=X6+H7ZHluBgqX31COwXi+VJfsmXI2TwGQk5JssE7KwY='
                ],
                'method'  => 'POST',
                'url'     => '/typeform/webhook_consumption',
                'content' => [
                    "event_id"      => "LtWXD3crgy",
                    "event_type"    => "form_response",
                    "form_response" => [
                        "form_id"      => "lT4Z3j",
                        "token"        => "a3a12ec67a1365927098a606107fac15",
                        "submitted_at" => "2018-01-18T18:17:02Z",
                        "landed_at"    => "2018-01-18T18:07:02Z",
                        "hidden"       => [
                            "mid" => "EV7j5qM0qca1U3"
                        ],
                        "calculated"   => [
                            "score" => 9
                        ],
                        "definition"   => [
                            "id"     => "lT4Z3j",
                            "title"  => "Webhooks example",
                            "fields" => [
                                [
                                    "id"                        => "DlXFaesGBpoF",
                                    "title"                     => "Thanks, {{answer_60906475}}! What's it like where you live? Tell us in a few sentences.",
                                    "type"                      => "long_text",
                                    "ref"                       => "[readable_ref_long_text",
                                    "allow_multiple_selections" => false,
                                    "allow_other_choice"        => false
                                ],
                                [
                                    "id"                        => "SMEUb7VJz92Q",
                                    "title"                     => "If you're OK with our city management following up if they have further questions, please give us your email address.",
                                    "type"                      => "email",
                                    "ref"                       => "readable_ref_email",
                                    "allow_multiple_selections" => false,
                                    "allow_other_choice"        => false
                                ],
                                [
                                    "id"                        => "JwWggjAKtOkA",
                                    "title"                     => "What is your first name?",
                                    "type"                      => "short_text",
                                    "ref"                       => "readable_ref_short_text",
                                    "allow_multiple_selections" => false,
                                    "allow_other_choice"        => false
                                ],
                                [
                                    "id"                        => "KoJxDM3c6x8h",
                                    "title"                     => "When did you move to the place where you live?",
                                    "type"                      => "date",
                                    "ref"                       => "readable_ref_date",
                                    "allow_multiple_selections" => false,
                                    "allow_other_choice"        => false
                                ],
                                [
                                    "id"                        => "PNe8ZKBK8C2Q",
                                    "title"                     => "Which pictures do you like? You can choose as many as you like.",
                                    "type"                      => "picture_choice",
                                    "ref"                       => "readable_ref_picture_choice",
                                    "allow_multiple_selections" => true,
                                    "allow_other_choice"        => false
                                ],
                                [
                                    "id"                        => "Q7M2XAwY04dW",
                                    "title"                     => "On a scale of 1 to 5, what rating would you give the weather in Sydney? 1 is poor weather, 5 is excellent weather",
                                    "type"                      => "number",
                                    "ref"                       => "readable_ref_number1",
                                    "allow_multiple_selections" => false,
                                    "allow_other_choice"        => false
                                ],
                                [
                                    "id"                        => "gFFf3xAkJKsr",
                                    "title"                     => "By submitting this form, you understand and accept that we will share your answers with city management. Your answers will be anonymous will not be shared.",
                                    "type"                      => "legal",
                                    "ref"                       => "readable_ref_legal",
                                    "allow_multiple_selections" => false,
                                    "allow_other_choice"        => false
                                ],
                                [
                                    "id"                        => "k6TP9oLGgHjl",
                                    "title"                     => "Which of these cities is your favorite?",
                                    "type"                      => "multiple_choice",
                                    "ref"                       => "readable_ref_multiple_choice",
                                    "allow_multiple_selections" => false,
                                    "allow_other_choice"        => false
                                ],
                                [
                                    "id"                        => "RUqkXSeXBXSd",
                                    "title"                     => "Do you have a favorite city we haven't listed?",
                                    "type"                      => "yes_no",
                                    "ref"                       => "readable_ref_yes_no",
                                    "allow_multiple_selections" => false,
                                    "allow_other_choice"        => false
                                ],
                                [
                                    "id"                        => "NRsxU591jIW9",
                                    "title"                     => "How important is the weather to your opinion about a city? 1 is not important, 5 is very important.",
                                    "type"                      => "opinion_scale",
                                    "ref"                       => "readable_ref_opinion_scale",
                                    "allow_multiple_selections" => false,
                                    "allow_other_choice"        => false
                                ],
                                [
                                    "id"                        => "WOTdC00F8A3h",
                                    "title"                     => "How would you rate the weather where you currently live? 1 is poor weather, 5 is excellent weather.",
                                    "type"                      => "rating",
                                    "ref"                       => "readable_ref_rating",
                                    "allow_multiple_selections" => false,
                                    "allow_other_choice"        => false
                                ],
                                [
                                    "id"                        => "pn48RmPazVdM",
                                    "title"                     => "On a scale of 1 to 5, what rating would you give the general quality of life in Sydney? 1 is poor, 5 is excellent",
                                    "type"                      => "number",
                                    "ref"                       => "readable_ref_number2",
                                    "allow_multiple_selections" => false,
                                    "allow_other_choice"        => false
                                ]
                            ]
                        ],
                        "answers"      => [
                            [
                                "type"  => "text",
                                "text"  => "It's cold right now! I live in an older medium-sized city with a university. Geographically, the area is hilly.",
                                "field" => [
                                    "id"   => "DlXFaesGBpoF",
                                    "type" => "long_text"
                                ]
                            ],
                            [
                                "type"  => "email",
                                "email" => "laura@example.com",
                                "field" => [
                                    "id"   => "SMEUb7VJz92Q",
                                    "type" => "email"
                                ]
                            ],
                            [
                                "type"  => "text",
                                "text"  => "Laura",
                                "field" => [
                                    "id"   => "JwWggjAKtOkA",
                                    "type" => "short_text"
                                ]
                            ],
                            [
                                "type"  => "date",
                                "date"  => "2005-10-15",
                                "field" => [
                                    "id"   => "KoJxDM3c6x8h",
                                    "type" => "date"
                                ]
                            ],
                            [
                                "type"    => "choices",
                                "choices" => [
                                    "labels" => [
                                        "London",
                                        "Sydney"
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
                            [
                                "type"    => "boolean",
                                "boolean" => true,
                                "field"   => [
                                    "id"   => "gFFf3xAkJKsr",
                                    "type" => "legal"
                                ]
                            ],
                            [
                                "type"   => "choice",
                                "choice" => [
                                    "label" => "London"
                                ],
                                "field"  => [
                                    "id"   => "k6TP9oLGgHjl",
                                    "type" => "multiple_choice"
                                ]
                            ],
                            [
                                "type"    => "boolean",
                                "boolean" => false,
                                "field"   => [
                                    "id"   => "RUqkXSeXBXSd",
                                    "type" => "yes_no"
                                ]
                            ],
                            [
                                "type"   => "number",
                                "number" => 2,
                                "field"  => [
                                    "id"   => "NRsxU591jIW9",
                                    "type" => "opinion_scale"
                                ]
                            ],
                            [
                                "type"   => "number",
                                "number" => 3,
                                "field"  => [
                                    "id"   => "WOTdC00F8A3h",
                                    "type" => "rating"
                                ]
                            ],
                            [
                                "type"   => "number",
                                "number" => 4,
                                "field"  => [
                                    "id"   => "pn48RmPazVdM",
                                    "type" => "number"
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            'response'    => [
                'content' => [
                    'success' => true
                ],
            ],
            'status_code' => 200,
        ],

        'testInvalidDataTypeformWebhookConsumption' => [
            'request'   => [
                'server'  => [
                    'HTTP_TYPEFORM_SIGNATURE' => 'sha256=X6+H7ZHluBgqX31COwXi+VJfsmXI2TwGQk5JssE7KwY='
                ],
                'method'  => 'POST',
                'url'     => '/typeform/webhook_consumption',
                'content' => [
                    'event_id'   => '01E1BYRNFK9BD6YCH7PXRN0XV8',
                    'event_type' => 'form_response',
                ],
            ],
            'response'  => [
                'content'     => [
                    'error' => [
                        'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    ],
                ],
                'status_code' => 400,
            ],
            'exception' => [
                'class'               => 'RZP\Exception\BadRequestValidationFailureException',
                'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
            ],
        ],

        'testApprovalTypeformWebhookConsumption' => [
            'request'     => [
                'server'  => [
                    'HTTP_TYPEFORM_SIGNATURE' => 'sha256=X6+H7ZHluBgqX31COwXi+VJfsmXI2TwGQk5JssE7KwY='
                ],
                'method'  => 'POST',
                'url'     => '/typeform/webhook_consumption',
                'content' => [
                    "permission"    => "edit_merchant_pg_international",
                    "event_id"      => "LtWXD3crgy",
                    "event_type"    => "form_response",
                    "form_response" => [
                        "form_id"      => "lT4Z3j",
                        "token"        => "a3a12ec67a1365927098a606107fac15",
                        "submitted_at" => "2018-01-18T18:17:02Z",
                        "landed_at"    => "2018-01-18T18:07:02Z",
                        "hidden"       => [
                            "mid" => "EV7j5qM0qca1U3"
                        ],
                        "calculated"   => [
                            "score" => 9
                        ],
                        "definition"   => [
                            "id"     => "lT4Z3j",
                            "title"  => "Webhooks example",
                            "fields" => [
                                [
                                    "id"                        => "DlXFaesGBpoF",
                                    "title"                     => "Thanks, User! What's it like where you live? Tell us in a few sentences.",
                                    "type"                      => "long_text",
                                    "ref"                       => "[readable_ref_long_text",
                                    "allow_multiple_selections" => false,
                                    "allow_other_choice"        => false
                                ],
                                [
                                    "id"                        => "SMEUb7VJz92Q",
                                    "title"                     => "If you're OK with our city management following up if they have further questions, please give us your email address.",
                                    "type"                      => "email",
                                    "ref"                       => "readable_ref_email",
                                    "allow_multiple_selections" => false,
                                    "allow_other_choice"        => false
                                ],
                                [
                                    "id"                        => "JwWggjAKtOkA",
                                    "title"                     => "What is your first name?",
                                    "type"                      => "short_text",
                                    "ref"                       => "readable_ref_short_text",
                                    "allow_multiple_selections" => false,
                                    "allow_other_choice"        => false
                                ],
                                [
                                    "id"                        => "KoJxDM3c6x8h",
                                    "title"                     => "When did you move to the place where you live?",
                                    "type"                      => "date",
                                    "ref"                       => "readable_ref_date",
                                    "allow_multiple_selections" => false,
                                    "allow_other_choice"        => false
                                ],
                                [
                                    "id"                        => "PNe8ZKBK8C2Q",
                                    "title"                     => "Which pictures do you like? You can choose as many as you like.",
                                    "type"                      => "picture_choice",
                                    "ref"                       => "readable_ref_picture_choice",
                                    "allow_multiple_selections" => true,
                                    "allow_other_choice"        => false
                                ],
                                [
                                    "id"                        => "Q7M2XAwY04dW",
                                    "title"                     => "On a scale of 1 to 5, what rating would you give the weather in Sydney? 1 is poor weather, 5 is excellent weather",
                                    "type"                      => "number",
                                    "ref"                       => "readable_ref_number1",
                                    "allow_multiple_selections" => false,
                                    "allow_other_choice"        => false
                                ],
                                [
                                    "id"                        => "gFFf3xAkJKsr",
                                    "title"                     => "By submitting this form, you understand and accept that we will share your answers with city management. Your answers will be anonymous will not be shared.",
                                    "type"                      => "legal",
                                    "ref"                       => "readable_ref_legal",
                                    "allow_multiple_selections" => false,
                                    "allow_other_choice"        => false
                                ],
                                [
                                    "id"                        => "k6TP9oLGgHjl",
                                    "title"                     => "Which of these cities is your favorite?",
                                    "type"                      => "multiple_choice",
                                    "ref"                       => "readable_ref_multiple_choice",
                                    "allow_multiple_selections" => false,
                                    "allow_other_choice"        => false
                                ],
                                [
                                    "id"                        => "RUqkXSeXBXSd",
                                    "title"                     => "Do you have a favorite city we haven't listed?",
                                    "type"                      => "yes_no",
                                    "ref"                       => "readable_ref_yes_no",
                                    "allow_multiple_selections" => false,
                                    "allow_other_choice"        => false
                                ],
                                [
                                    "id"                        => "NRsxU591jIW9",
                                    "title"                     => "How important is the weather to your opinion about a city? 1 is not important, 5 is very important.",
                                    "type"                      => "opinion_scale",
                                    "ref"                       => "readable_ref_opinion_scale",
                                    "allow_multiple_selections" => false,
                                    "allow_other_choice"        => false
                                ],
                                [
                                    "id"                        => "WOTdC00F8A3h",
                                    "title"                     => "How would you rate the weather where you currently live? 1 is poor weather, 5 is excellent weather.",
                                    "type"                      => "rating",
                                    "ref"                       => "readable_ref_rating",
                                    "allow_multiple_selections" => false,
                                    "allow_other_choice"        => false
                                ],
                                [
                                    "id"                        => "pn48RmPazVdM",
                                    "title"                     => "On a scale of 1 to 5, what rating would you give the general quality of life in Sydney? 1 is poor, 5 is excellent",
                                    "type"                      => "number",
                                    "ref"                       => "readable_ref_number2",
                                    "allow_multiple_selections" => false,
                                    "allow_other_choice"        => false
                                ]
                            ]
                        ],
                        "answers"      => [
                            [
                                "type"  => "text",
                                "text"  => "It's cold right now! I live in an older medium-sized city with a university. Geographically, the area is hilly.",
                                "field" => [
                                    "id"   => "DlXFaesGBpoF",
                                    "type" => "long_text"
                                ]
                            ],
                            [
                                "type"  => "email",
                                "email" => "laura@example.com",
                                "field" => [
                                    "id"   => "SMEUb7VJz92Q",
                                    "type" => "email"
                                ]
                            ],
                            [
                                "type"  => "text",
                                "text"  => "Laura",
                                "field" => [
                                    "id"   => "JwWggjAKtOkA",
                                    "type" => "short_text"
                                ]
                            ],
                            [
                                "type"  => "date",
                                "date"  => "2005-10-15",
                                "field" => [
                                    "id"   => "KoJxDM3c6x8h",
                                    "type" => "date"
                                ]
                            ],
                            [
                                "type"    => "choices",
                                "choices" => [
                                    "labels" => [
                                        "London",
                                        "Sydney"
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
                            [
                                "type"    => "boolean",
                                "boolean" => true,
                                "field"   => [
                                    "id"   => "gFFf3xAkJKsr",
                                    "type" => "legal"
                                ]
                            ],
                            [
                                "type"   => "choice",
                                "choice" => [
                                    "label" => "London"
                                ],
                                "field"  => [
                                    "id"   => "k6TP9oLGgHjl",
                                    "type" => "multiple_choice"
                                ]
                            ],
                            [
                                "type"    => "boolean",
                                "boolean" => false,
                                "field"   => [
                                    "id"   => "RUqkXSeXBXSd",
                                    "type" => "yes_no"
                                ]
                            ],
                            [
                                "type"   => "number",
                                "number" => 2,
                                "field"  => [
                                    "id"   => "NRsxU591jIW9",
                                    "type" => "opinion_scale"
                                ]
                            ],
                            [
                                "type"   => "number",
                                "number" => 3,
                                "field"  => [
                                    "id"   => "WOTdC00F8A3h",
                                    "type" => "rating"
                                ]
                            ],
                            [
                                "type"   => "number",
                                "number" => 4,
                                "field"  => [
                                    "id"   => "pn48RmPazVdM",
                                    "type" => "number"
                                ]
                            ]
                        ]
                    ]
                ],
            ],
            'response'    => [
                'content' => [
                    'success' => true
                ],
            ],
            'status_code' => 200,
        ],

        'testApprovalTypeformWebhookConsumptionMobileSignUp' => [
            'request'     => [
                'server'  => [
                    'HTTP_TYPEFORM_SIGNATURE' => 'sha256=X6+H7ZHluBgqX31COwXi+VJfsmXI2TwGQk5JssE7KwY='
                ],
                'method'  => 'POST',
                'url'     => '/typeform/webhook_consumption',
                'content' => [
                    "permission"    => "edit_merchant_pg_international",
                    "event_id"      => "LtWXD3crgy",
                    "event_type"    => "form_response",
                    "form_response" => [
                        "form_id"      => "lT4Z3j",
                        "token"        => "a3a12ec67a1365927098a606107fac15",
                        "submitted_at" => "2018-01-18T18:17:02Z",
                        "landed_at"    => "2018-01-18T18:07:02Z",
                        "hidden"       => [
                            "mid" => "EV7j5qM0qca1U3"
                        ],
                        "calculated"   => [
                            "score" => 9
                        ],
                        "definition"   => [
                            "id"     => "lT4Z3j",
                            "title"  => "Webhooks example",
                            "fields" => [
                                [
                                    "id"                        => "DlXFaesGBpoF",
                                    "title"                     => "Thanks, User! What's it like where you live? Tell us in a few sentences.",
                                    "type"                      => "long_text",
                                    "ref"                       => "[readable_ref_long_text",
                                    "allow_multiple_selections" => false,
                                    "allow_other_choice"        => false
                                ],
                                [
                                    "id"                        => "SMEUb7VJz92Q",
                                    "title"                     => "If you're OK with our city management following up if they have further questions, please give us your email address.",
                                    "type"                      => "email",
                                    "ref"                       => "readable_ref_email",
                                    "allow_multiple_selections" => false,
                                    "allow_other_choice"        => false
                                ],
                                [
                                    "id"                        => "JwWggjAKtOkA",
                                    "title"                     => "What is your first name?",
                                    "type"                      => "short_text",
                                    "ref"                       => "readable_ref_short_text",
                                    "allow_multiple_selections" => false,
                                    "allow_other_choice"        => false
                                ],
                                [
                                    "id"                        => "KoJxDM3c6x8h",
                                    "title"                     => "When did you move to the place where you live?",
                                    "type"                      => "date",
                                    "ref"                       => "readable_ref_date",
                                    "allow_multiple_selections" => false,
                                    "allow_other_choice"        => false
                                ],
                                [
                                    "id"                        => "PNe8ZKBK8C2Q",
                                    "title"                     => "Which pictures do you like? You can choose as many as you like.",
                                    "type"                      => "picture_choice",
                                    "ref"                       => "readable_ref_picture_choice",
                                    "allow_multiple_selections" => true,
                                    "allow_other_choice"        => false
                                ],
                                [
                                    "id"                        => "Q7M2XAwY04dW",
                                    "title"                     => "On a scale of 1 to 5, what rating would you give the weather in Sydney? 1 is poor weather, 5 is excellent weather",
                                    "type"                      => "number",
                                    "ref"                       => "readable_ref_number1",
                                    "allow_multiple_selections" => false,
                                    "allow_other_choice"        => false
                                ],
                                [
                                    "id"                        => "gFFf3xAkJKsr",
                                    "title"                     => "By submitting this form, you understand and accept that we will share your answers with city management. Your answers will be anonymous will not be shared.",
                                    "type"                      => "legal",
                                    "ref"                       => "readable_ref_legal",
                                    "allow_multiple_selections" => false,
                                    "allow_other_choice"        => false
                                ],
                                [
                                    "id"                        => "k6TP9oLGgHjl",
                                    "title"                     => "Which of these cities is your favorite?",
                                    "type"                      => "multiple_choice",
                                    "ref"                       => "readable_ref_multiple_choice",
                                    "allow_multiple_selections" => false,
                                    "allow_other_choice"        => false
                                ],
                                [
                                    "id"                        => "RUqkXSeXBXSd",
                                    "title"                     => "Do you have a favorite city we haven't listed?",
                                    "type"                      => "yes_no",
                                    "ref"                       => "readable_ref_yes_no",
                                    "allow_multiple_selections" => false,
                                    "allow_other_choice"        => false
                                ],
                                [
                                    "id"                        => "NRsxU591jIW9",
                                    "title"                     => "How important is the weather to your opinion about a city? 1 is not important, 5 is very important.",
                                    "type"                      => "opinion_scale",
                                    "ref"                       => "readable_ref_opinion_scale",
                                    "allow_multiple_selections" => false,
                                    "allow_other_choice"        => false
                                ],
                                [
                                    "id"                        => "WOTdC00F8A3h",
                                    "title"                     => "How would you rate the weather where you currently live? 1 is poor weather, 5 is excellent weather.",
                                    "type"                      => "rating",
                                    "ref"                       => "readable_ref_rating",
                                    "allow_multiple_selections" => false,
                                    "allow_other_choice"        => false
                                ],
                                [
                                    "id"                        => "pn48RmPazVdM",
                                    "title"                     => "On a scale of 1 to 5, what rating would you give the general quality of life in Sydney? 1 is poor, 5 is excellent",
                                    "type"                      => "number",
                                    "ref"                       => "readable_ref_number2",
                                    "allow_multiple_selections" => false,
                                    "allow_other_choice"        => false
                                ]
                            ]
                        ],
                        "answers"      => [
                            [
                                "type"  => "text",
                                "text"  => "It's cold right now! I live in an older medium-sized city with a university. Geographically, the area is hilly.",
                                "field" => [
                                    "id"   => "DlXFaesGBpoF",
                                    "type" => "long_text"
                                ]
                            ],
                            [
                                "type"  => "email",
                                "email" => "laura@example.com",
                                "field" => [
                                    "id"   => "SMEUb7VJz92Q",
                                    "type" => "email"
                                ]
                            ],
                            [
                                "type"  => "text",
                                "text"  => "Laura",
                                "field" => [
                                    "id"   => "JwWggjAKtOkA",
                                    "type" => "short_text"
                                ]
                            ],
                            [
                                "type"  => "date",
                                "date"  => "2005-10-15",
                                "field" => [
                                    "id"   => "KoJxDM3c6x8h",
                                    "type" => "date"
                                ]
                            ],
                            [
                                "type"    => "choices",
                                "choices" => [
                                    "labels" => [
                                        "London",
                                        "Sydney"
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
                            [
                                "type"    => "boolean",
                                "boolean" => true,
                                "field"   => [
                                    "id"   => "gFFf3xAkJKsr",
                                    "type" => "legal"
                                ]
                            ],
                            [
                                "type"   => "choice",
                                "choice" => [
                                    "label" => "London"
                                ],
                                "field"  => [
                                    "id"   => "k6TP9oLGgHjl",
                                    "type" => "multiple_choice"
                                ]
                            ],
                            [
                                "type"    => "boolean",
                                "boolean" => false,
                                "field"   => [
                                    "id"   => "RUqkXSeXBXSd",
                                    "type" => "yes_no"
                                ]
                            ],
                            [
                                "type"   => "number",
                                "number" => 2,
                                "field"  => [
                                    "id"   => "NRsxU591jIW9",
                                    "type" => "opinion_scale"
                                ]
                            ],
                            [
                                "type"   => "number",
                                "number" => 3,
                                "field"  => [
                                    "id"   => "WOTdC00F8A3h",
                                    "type" => "rating"
                                ]
                            ],
                            [
                                "type"   => "number",
                                "number" => 4,
                                "field"  => [
                                    "id"   => "pn48RmPazVdM",
                                    "type" => "number"
                                ]
                            ]
                        ]
                    ]
                ],
            ],
            'response'    => [
                'content' => [
                    'success' => true
                ],
            ],
            'status_code' => 200,
        ],

        'testProd2ApprovalTypeformWebhookConsumption' => [
            'request'     => [
                'server'  => [
                    'HTTP_TYPEFORM_SIGNATURE' => 'sha256=X6+H7ZHluBgqX31COwXi+VJfsmXI2TwGQk5JssE7KwY='
                ],
                'method'  => 'POST',
                'url'     => '/typeform/webhook_consumption',
                'content' => [
                    "permission"    => "edit_merchant_prod_v2_international",
                    "event_id"      => "LtWXD3crgy",
                    "event_type"    => "form_response",
                    "form_response" => [
                        "form_id"      => "lT4Z3j",
                        "token"        => "a3a12ec67a1365927098a606107fac15",
                        "submitted_at" => "2018-01-18T18:17:02Z",
                        "landed_at"    => "2018-01-18T18:07:02Z",
                        "hidden"       => [
                            "mid" => "EV7j5qM0qca1U3"
                        ],
                        "calculated"   => [
                            "score" => 9
                        ],
                        "definition"   => [
                            "id"     => "lT4Z3j",
                            "title"  => "Webhooks example",
                            "fields" => [
                                [
                                    "id"                        => "DlXFaesGBpoF",
                                    "title"                     => "Thanks, User! What's it like where you live? Tell us in a few sentences.",
                                    "type"                      => "long_text",
                                    "ref"                       => "[readable_ref_long_text",
                                    "allow_multiple_selections" => false,
                                    "allow_other_choice"        => false
                                ],
                                [
                                    "id"                        => "SMEUb7VJz92Q",
                                    "title"                     => "If you're OK with our city management following up if they have further questions, please give us your email address.",
                                    "type"                      => "email",
                                    "ref"                       => "readable_ref_email",
                                    "allow_multiple_selections" => false,
                                    "allow_other_choice"        => false
                                ],
                            ]
                        ],
                        "answers"      => [
                            [
                                "type"  => "text",
                                "text"  => "It's cold right now! I live in an older medium-sized city with a university. Geographically, the area is hilly.",
                                "field" => [
                                    "id"   => "DlXFaesGBpoF",
                                    "type" => "long_text"
                                ]
                            ],
                            [
                                "type"  => "email",
                                "email" => "laura@example.com",
                                "field" => [
                                    "id"   => "SMEUb7VJz92Q",
                                    "type" => "email"
                                ]
                            ],
                        ]
                    ]
                ],
            ],
            'response'    => [
                'content' => [
                    'success' => true
                ],
            ],
            'status_code' => 200,
        ],

        'testApprovalTypeformWebhookConsumptionInvalidWebsite' => [
            'request'   => [
                'server'  => [
                    'HTTP_TYPEFORM_SIGNATURE' => 'sha256=X6+H7ZHluBgqX31COwXi+VJfsmXI2TwGQk5JssE7KwY='
                ],
                'method'  => 'POST',
                'url'     => '/typeform/webhook_consumption',
                'content' => [
                    "permission"    => "edit_merchant_prod_v2_international",
                    "event_id"      => "LtWXD3crgy",
                    "event_type"    => "form_response",
                    "form_response" => [
                        "form_id"      => "lT4Z3j",
                        "token"        => "a3a12ec67a1365927098a606107fac15",
                        "submitted_at" => "2018-01-18T18:17:02Z",
                        "landed_at"    => "2018-01-18T18:07:02Z",
                        "hidden"       => [
                            "mid" => "EV7j5qM0qca1U3"
                        ],
                        "calculated"   => [
                            "score" => 9
                        ],
                        "definition"   => [
                            "id"     => "lT4Z3j",
                            "title"  => "Webhooks example",
                            "fields" => [
                                [
                                    "id"                        => "DlXFaesGBpoF",
                                    "title"                     => "Thanks, User! What's it like where you live? Tell us in a few sentences.",
                                    "type"                      => "long_text",
                                    "ref"                       => "[readable_ref_long_text",
                                    "allow_multiple_selections" => false,
                                    "allow_other_choice"        => false
                                ],
                                [
                                    "id"                        => "SMEUb7VJz92Q",
                                    "title"                     => "If you're OK with our city management following up if they have further questions, please give us your email address.",
                                    "type"                      => "email",
                                    "ref"                       => "readable_ref_email",
                                    "allow_multiple_selections" => false,
                                    "allow_other_choice"        => false
                                ],
                            ]
                        ],
                        "answers"      => [
                            [
                                "type"  => "text",
                                "text"  => "It's cold right now! I live in an older medium-sized city with a university. Geographically, the area is hilly.",
                                "field" => [
                                    "id"   => "DlXFaesGBpoF",
                                    "type" => "long_text"
                                ]
                            ],
                            [
                                "type"  => "email",
                                "email" => "laura@example.com",
                                "field" => [
                                    "id"   => "SMEUb7VJz92Q",
                                    "type" => "email"
                                ]
                            ],
                        ]
                    ]
                ],
            ],
            'response'  => [
                'content'     => [
                    'error' => [
                        'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    ],
                ],
                'status_code' => 400,
            ],
            'exception' => [
                'class'               => 'RZP\Exception\BadRequestValidationFailureException',
                'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
                'message'             => 'Workflow can\'t be approved as Merchant Website is not Valid',
            ],
        ],

        'testWorkflowCreationTypeformWebhook' => [
            'request'     => [
                'server'  => [
                    'HTTP_TYPEFORM_SIGNATURE' => 'sha256=X6+H7ZHluBgqX31COwXi+VJfsmXI2TwGQk5JssE7KwY='
                ],
                'method'  => 'POST',
                'url'     => '/typeform/webhook_consumption',
                'content' => [
                    "event_id"      => "LtWXD3crgy",
                    "event_type"    => "form_response",
                    "form_response" => [
                        "form_id"      => "lT4Z3j",
                        "token"        => "a3a12ec67a1365927098a606107fac15",
                        "submitted_at" => "2018-01-18T18:17:02Z",
                        "landed_at"    => "2018-01-18T18:07:02Z",
                        "hidden"       => [
                            "mid" => "EV7j5qM0qca1U3"
                        ],
                        "calculated"   => [
                            "score" => 9
                        ],
                        "definition"   => [
                            "id"     => "lT4Z3j",
                            "title"  => "Webhooks example",
                            "fields" => [
                                [
                                    "id"                        => "DlXFaesGBpoF",
                                    "title"                     => "Thanks, User! What's it like where you live? Tell us in a few sentences.",
                                    "type"                      => "long_text",
                                    "ref"                       => "[readable_ref_long_text",
                                    "allow_multiple_selections" => false,
                                    "allow_other_choice"        => false
                                ],
                                [
                                    "id"                        => "SMEUb7VJz92Q",
                                    "title"                     => "If you're OK with our city management following up if they have further questions, please give us your email address.",
                                    "type"                      => "email",
                                    "ref"                       => "readable_ref_email",
                                    "allow_multiple_selections" => false,
                                    "allow_other_choice"        => false
                                ],
                            ]
                        ],
                        "answers"      => [
                            [
                                "type"  => "text",
                                "text"  => "It's cold right now! I live in an older medium-sized city with a university. Geographically, the area is hilly.",
                                "field" => [
                                    "id"   => "DlXFaesGBpoF",
                                    "type" => "long_text"
                                ]
                            ],
                            [
                                "type"  => "email",
                                "email" => "laura@example.com",
                                "field" => [
                                    "id"   => "SMEUb7VJz92Q",
                                    "type" => "email"
                                ]
                            ],
                        ]
                    ]
                ],
            ],
            'response'    => [
                'content' => [
                    'success' => true
                ],
            ],
            'status_code' => 200,
        ],

        'testOldWorkflowsExecution' => [
            'request'     => [
                'server'  => [
                    'HTTP_TYPEFORM_SIGNATURE' => 'sha256=X6+H7ZHluBgqX31COwXi+VJfsmXI2TwGQk5JssE7KwY='
                ],
                'method'  => 'POST',
                'url'     => '/typeform/webhook_consumption',
                'content' => [
                    "permission"    => "edit_merchant_international",
                    "event_id"      => "LtWXD3crgy",
                    "event_type"    => "form_response",
                    "form_response" => [
                        "form_id"      => "lT4Z3j",
                        "token"        => "a3a12ec67a1365927098a606107fac15",
                        "submitted_at" => "2018-01-18T18:17:02Z",
                        "landed_at"    => "2018-01-18T18:07:02Z",
                        "hidden"       => [
                            "mid" => "EV7j5qM0qca1U3"
                        ],
                        "calculated"   => [
                            "score" => 9
                        ],
                        "definition"   => [
                            "id"     => "lT4Z3j",
                            "title"  => "Webhooks example",
                            "fields" => [
                                [
                                    "id"                        => "DlXFaesGBpoF",
                                    "title"                     => "Thanks, User! What's it like where you live? Tell us in a few sentences.",
                                    "type"                      => "long_text",
                                    "ref"                       => "[readable_ref_long_text",
                                    "allow_multiple_selections" => false,
                                    "allow_other_choice"        => false
                                ],
                                [
                                    "id"                        => "SMEUb7VJz92Q",
                                    "title"                     => "If you're OK with our city management following up if they have further questions, please give us your email address.",
                                    "type"                      => "email",
                                    "ref"                       => "readable_ref_email",
                                    "allow_multiple_selections" => false,
                                    "allow_other_choice"        => false
                                ],
                            ]
                        ],
                        "answers"      => [
                            [
                                "type"  => "text",
                                "text"  => "It's cold right now! I live in an older medium-sized city with a university. Geographically, the area is hilly.",
                                "field" => [
                                    "id"   => "DlXFaesGBpoF",
                                    "type" => "long_text"
                                ]
                            ],
                            [
                                "type"  => "email",
                                "email" => "laura@example.com",
                                "field" => [
                                    "id"   => "SMEUb7VJz92Q",
                                    "type" => "email"
                                ]
                            ],
                        ]
                    ]
                ],
            ],
            'response'    => [
                'content' => [
                    'success' => true
                ],
            ],
            'status_code' => 200,
        ],

        'testApprovalTypeformWebhookConsumptionNotification' => [
            'request'     => [
                'server'  => [
                    'HTTP_TYPEFORM_SIGNATURE' => 'sha256=X6+H7ZHluBgqX31COwXi+VJfsmXI2TwGQk5JssE7KwY='
                ],
                'method'  => 'POST',
                'url'     => '/typeform/webhook_consumption',
                'content' => [
                    "permission"    => "edit_merchant_pg_international",
                    "event_id"      => "LtWXD3crgy",
                    "event_type"    => "form_response",
                    "form_response" => [
                        "form_id"      => "lT4Z3j",
                        "token"        => "a3a12ec67a1365927098a606107fac15",
                        "submitted_at" => "2018-01-18T18:17:02Z",
                        "landed_at"    => "2018-01-18T18:07:02Z",
                        "hidden"       => [
                            "mid" => "EV7j5qM0qca1U3"
                        ],
                        "calculated"   => [
                            "score" => 9
                        ],
                        "definition"   => [
                            "id"     => "lT4Z3j",
                            "title"  => "Webhooks example",
                            "fields" => [
                                [
                                    "id"                        => "DlXFaesGBpoF",
                                    "title"                     => "Thanks, User! What's it like where you live? Tell us in a few sentences.",
                                    "type"                      => "long_text",
                                    "ref"                       => "[readable_ref_long_text",
                                    "allow_multiple_selections" => false,
                                    "allow_other_choice"        => false
                                ],
                                [
                                    "id"                        => "SMEUb7VJz92Q",
                                    "title"                     => "If you're OK with our city management following up if they have further questions, please give us your email address.",
                                    "type"                      => "email",
                                    "ref"                       => "readable_ref_email",
                                    "allow_multiple_selections" => false,
                                    "allow_other_choice"        => false
                                ],
                                [
                                    "id"                        => "JwWggjAKtOkA",
                                    "title"                     => "What is your first name?",
                                    "type"                      => "short_text",
                                    "ref"                       => "readable_ref_short_text",
                                    "allow_multiple_selections" => false,
                                    "allow_other_choice"        => false
                                ],
                                [
                                    "id"                        => "KoJxDM3c6x8h",
                                    "title"                     => "When did you move to the place where you live?",
                                    "type"                      => "date",
                                    "ref"                       => "readable_ref_date",
                                    "allow_multiple_selections" => false,
                                    "allow_other_choice"        => false
                                ],
                                [
                                    "id"                        => "PNe8ZKBK8C2Q",
                                    "title"                     => "Which pictures do you like? You can choose as many as you like.",
                                    "type"                      => "picture_choice",
                                    "ref"                       => "readable_ref_picture_choice",
                                    "allow_multiple_selections" => true,
                                    "allow_other_choice"        => false
                                ],
                                [
                                    "id"                        => "Q7M2XAwY04dW",
                                    "title"                     => "On a scale of 1 to 5, what rating would you give the weather in Sydney? 1 is poor weather, 5 is excellent weather",
                                    "type"                      => "number",
                                    "ref"                       => "readable_ref_number1",
                                    "allow_multiple_selections" => false,
                                    "allow_other_choice"        => false
                                ],
                                [
                                    "id"                        => "gFFf3xAkJKsr",
                                    "title"                     => "By submitting this form, you understand and accept that we will share your answers with city management. Your answers will be anonymous will not be shared.",
                                    "type"                      => "legal",
                                    "ref"                       => "readable_ref_legal",
                                    "allow_multiple_selections" => false,
                                    "allow_other_choice"        => false
                                ],
                                [
                                    "id"                        => "k6TP9oLGgHjl",
                                    "title"                     => "Which of these cities is your favorite?",
                                    "type"                      => "multiple_choice",
                                    "ref"                       => "readable_ref_multiple_choice",
                                    "allow_multiple_selections" => false,
                                    "allow_other_choice"        => false
                                ],
                                [
                                    "id"                        => "RUqkXSeXBXSd",
                                    "title"                     => "Do you have a favorite city we haven't listed?",
                                    "type"                      => "yes_no",
                                    "ref"                       => "readable_ref_yes_no",
                                    "allow_multiple_selections" => false,
                                    "allow_other_choice"        => false
                                ],
                                [
                                    "id"                        => "NRsxU591jIW9",
                                    "title"                     => "How important is the weather to your opinion about a city? 1 is not important, 5 is very important.",
                                    "type"                      => "opinion_scale",
                                    "ref"                       => "readable_ref_opinion_scale",
                                    "allow_multiple_selections" => false,
                                    "allow_other_choice"        => false
                                ],
                                [
                                    "id"                        => "WOTdC00F8A3h",
                                    "title"                     => "How would you rate the weather where you currently live? 1 is poor weather, 5 is excellent weather.",
                                    "type"                      => "rating",
                                    "ref"                       => "readable_ref_rating",
                                    "allow_multiple_selections" => false,
                                    "allow_other_choice"        => false
                                ],
                                [
                                    "id"                        => "pn48RmPazVdM",
                                    "title"                     => "On a scale of 1 to 5, what rating would you give the general quality of life in Sydney? 1 is poor, 5 is excellent",
                                    "type"                      => "number",
                                    "ref"                       => "readable_ref_number2",
                                    "allow_multiple_selections" => false,
                                    "allow_other_choice"        => false
                                ]
                            ]
                        ],
                        "answers"      => [
                            [
                                "type"  => "text",
                                "text"  => "It's cold right now! I live in an older medium-sized city with a university. Geographically, the area is hilly.",
                                "field" => [
                                    "id"   => "DlXFaesGBpoF",
                                    "type" => "long_text"
                                ]
                            ],
                            [
                                "type"  => "email",
                                "email" => "laura@example.com",
                                "field" => [
                                    "id"   => "SMEUb7VJz92Q",
                                    "type" => "email"
                                ]
                            ],
                            [
                                "type"  => "text",
                                "text"  => "Laura",
                                "field" => [
                                    "id"   => "JwWggjAKtOkA",
                                    "type" => "short_text"
                                ]
                            ],
                            [
                                "type"  => "date",
                                "date"  => "2005-10-15",
                                "field" => [
                                    "id"   => "KoJxDM3c6x8h",
                                    "type" => "date"
                                ]
                            ],
                            [
                                "type"    => "choices",
                                "choices" => [
                                    "labels" => [
                                        "London",
                                        "Sydney"
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
                            [
                                "type"    => "boolean",
                                "boolean" => true,
                                "field"   => [
                                    "id"   => "gFFf3xAkJKsr",
                                    "type" => "legal"
                                ]
                            ],
                            [
                                "type"   => "choice",
                                "choice" => [
                                    "label" => "London"
                                ],
                                "field"  => [
                                    "id"   => "k6TP9oLGgHjl",
                                    "type" => "multiple_choice"
                                ]
                            ],
                            [
                                "type"    => "boolean",
                                "boolean" => false,
                                "field"   => [
                                    "id"   => "RUqkXSeXBXSd",
                                    "type" => "yes_no"
                                ]
                            ],
                            [
                                "type"   => "number",
                                "number" => 2,
                                "field"  => [
                                    "id"   => "NRsxU591jIW9",
                                    "type" => "opinion_scale"
                                ]
                            ],
                            [
                                "type"   => "number",
                                "number" => 3,
                                "field"  => [
                                    "id"   => "WOTdC00F8A3h",
                                    "type" => "rating"
                                ]
                            ],
                            [
                                "type"   => "number",
                                "number" => 4,
                                "field"  => [
                                    "id"   => "pn48RmPazVdM",
                                    "type" => "number"
                                ]
                            ]
                        ]
                    ]
                ],
            ],
            'response'    => [
                'content' => [
                    'success' => true
                ],
            ],
            'status_code' => 200,
        ],

    ];
