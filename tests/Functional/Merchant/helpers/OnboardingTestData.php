<?php

use RZP\Models\Feature\Constants as FeaturesConstants;
use RZP\Models\Feature\Onboarding\Constants as OnboardingConstants;

return [
    'testGetQuestions'           => [
        'request'  => [
            'content' => [
                OnboardingConstants::FEATURES => [
                    FeaturesConstants::MARKETPLACE,
                    FeaturesConstants::SUBSCRIPTIONS,
                    FeaturesConstants::VIRTUAL_ACCOUNTS
                ]
            ],
            'url'     => '/feature/onboarding',
            'method'  => 'GET',
            'server'  => [
                'HTTP_X-Dashboard'            => 'true',
                'HTTP_X-Dashboard-User-Email' => 'user@rzp.dev',
            ],
        ],
        'response' => [
            'content' => [
                FeaturesConstants::MARKETPLACE      => [
                    OnboardingConstants::USE_CASE         => [
                        OnboardingConstants::ID            => OnboardingConstants::USE_CASE,
                        OnboardingConstants::RESPONSE_TYPE => 'textarea',
                        OnboardingConstants::MANDATORY     => true
                    ],
                    OnboardingConstants::SETTLING_TO      => [
                        OnboardingConstants::ID            => OnboardingConstants::SETTLING_TO,
                        OnboardingConstants::RESPONSE_TYPE => 'radio',
                        OnboardingConstants::MANDATORY     => true
                    ],
                    OnboardingConstants::VENDOR_AGREEMENT => [
                        OnboardingConstants::ID            => OnboardingConstants::VENDOR_AGREEMENT,
                        OnboardingConstants::RESPONSE_TYPE => 'file',
                        OnboardingConstants::MANDATORY     => false
                    ]
                ],
                FeaturesConstants::SUBSCRIPTIONS    => [
                    OnboardingConstants::BUSINESS_MODEL  => [
                        OnboardingConstants::ID            => OnboardingConstants::BUSINESS_MODEL,
                        OnboardingConstants::RESPONSE_TYPE => 'textarea',
                        OnboardingConstants::MANDATORY     => true
                    ],
                    OnboardingConstants::SAMPLE_PLANS    => [
                        OnboardingConstants::ID            => OnboardingConstants::SAMPLE_PLANS,
                        OnboardingConstants::RESPONSE_TYPE => 'textarea',
                        OnboardingConstants::MANDATORY     => true
                    ],
                    OnboardingConstants::WEBSITE_DETAILS => [
                        OnboardingConstants::ID            => OnboardingConstants::WEBSITE_DETAILS,
                        OnboardingConstants::RESPONSE_TYPE => 'textarea',
                        OnboardingConstants::MANDATORY     => true
                    ]
                ],
                FeaturesConstants::VIRTUAL_ACCOUNTS => [
                    OnboardingConstants::USE_CASE                 => [
                        OnboardingConstants::ID            => OnboardingConstants::USE_CASE,
                        OnboardingConstants::RESPONSE_TYPE => 'textarea',
                        OnboardingConstants::MANDATORY     => true
                    ],
                    OnboardingConstants::EXPECTED_MONTHLY_REVENUE => [
                        OnboardingConstants::ID            => OnboardingConstants::EXPECTED_MONTHLY_REVENUE,
                        OnboardingConstants::RESPONSE_TYPE => 'number',
                        OnboardingConstants::MANDATORY     => true
                    ]
                ]
            ],
        ],
    ],
    // Files will be added and verified from the main test function
    'testPostResponsesWithFiles' => [
        'request'  => [
            'content' => [
                FeaturesConstants::MARKETPLACE => [
                    OnboardingConstants::USE_CASE    => 'Some default use case',
                    OnboardingConstants::SETTLING_TO => 'Someone'
                ]
            ],
            'url'     => '/feature/onboarding',
            'method'  => 'POST',
            'server'  => [
                'HTTP_X-Dashboard'            => 'true',
                'HTTP_X-Dashboard-User-Email' => 'user@rzp.dev',
            ],
        ],
        'response' => [
            'content' => [
                FeaturesConstants::MARKETPLACE => [
                    OnboardingConstants::USE_CASE    => 'Some default use case',
                    OnboardingConstants::SETTLING_TO => 'Someone'
                ]
            ]
        ]
    ]
];
