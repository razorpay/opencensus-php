<?php

return [
    'testGetQuestions'           => [
        'request'  => [
            'content' => [
                'features' => [
                    'marketplace',
                    'subscriptions',
                    'virtual_accounts'
                ]
            ],
            'url'     => '/merchant/onboarding',
            'method'  => 'GET',
            'server'  => [
                'HTTP_X-Dashboard'            => 'true',
                'HTTP_X-Dashboard-User-Email' => 'user@rzp.dev',
            ],
        ],
        'response' => [
            'content' => [
                'onboarding' => [
                    'marketplace'      => [
                        'use_case'                          => [
                            'id'            => 'use_case',
                            'response_type' => 'textarea',
                            'mandatory'     => true
                        ],
                        'settling_to'                       => [
                            'id'            => 'settling_to',
                            'response_type' => 'radio',
                            'mandatory'     => true
                        ],
                        'signed_agreement_with_third_party' => [
                            'id'            => 'signed_agreement_with_third_party',
                            'response_type' => 'file',
                            'mandatory'     => false
                        ]
                    ],
                    'subscriptions'    => [
                        'business_model'  => [
                            'id'            => 'business_model',
                            'response_type' => 'textarea',
                            'mandatory'     => true
                        ],
                        'sample_plans'    => [
                            'id'            => 'sample_plans',
                            'response_type' => 'textarea',
                            'mandatory'     => true
                        ],
                        'website_details' => [
                            'id'            => 'website_details',
                            'response_type' => 'textarea',
                            'mandatory'     => true
                        ]
                    ],
                    'virtual_accounts' => [
                        'use_case'                 => [
                            'id'            => 'use_case',
                            'response_type' => 'textarea',
                            'mandatory'     => true
                        ],
                        'expected_monthly_revenue' => [
                            'id'            => 'expected_monthly_revenue',
                            'response_type' => 'number',
                            'mandatory'     => true
                        ]
                    ]
                ]
            ],
        ],
    ],
    // Files will be added and verified from the main test function
    'testpostResponsesWithFiles' => [
        'request'  => [
            'content' => [
                'onboarding' => [
                    'marketplace' => [
                        'use_case'    => 'Some default use case',
                        'settling_to' => 'Someone'
                    ]
                ],
            ],
            'url'     => '/merchant/onboarding',
            'method'  => 'POST',
            'server'  => [
                'HTTP_X-Dashboard'            => 'true',
                'HTTP_X-Dashboard-User-Email' => 'user@rzp.dev',
            ],
        ],
        'response' => [
            'content' => [
                'onboarding' => [
                    'marketplace' => [
                        'use_case'    => 'Some default use case',
                        'settling_to' => 'Someone'
                    ]
                ]
            ]
        ]
    ]
];
