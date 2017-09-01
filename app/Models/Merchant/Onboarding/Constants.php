<?php

namespace RZP\Models\Merchant\Onboarding;

use RZP\Models\Feature\Constants as FeatureConstants;
use RZP\Models\Base;

class Constants
{
    const USE_CASE                              = 'use_case';
    const SETTLING_TO                           = 'settling_to';
    const SIGNED_AGREEMENT_WITH_THIRD_PARTY     = 'singed_agreement_with_third_party';
    const BUSINESS_MODEL                        = 'business_model';
    const SAMPLE_PLANS                          = 'sample_plans';
    const WEBSITE_DETAILS                       = 'website_details';
    const EXPECTED_MONTHLY_REVENUE              = 'expected_monthly_revenue';

    // Constants used in API request params and responses
    const FEATURES                              = 'features';
    const MERCHANT                              = 'merchant';
    const QUESTIONS                             = 'questions';

    public static $questionMap = [
        self::USE_CASE => [
            'id'                    => self::USE_CASE,
            'question'              => 'What is your use case?',
            'description'           => '',
            'response_type'         => 'textarea',
            'available_responses'   => [ ],
            'mandatory'             => true
        ],
        self::SETTLING_TO => [
            'id'                    => self::SETTLING_TO,
            'question'              => 'Who are you settling to?',
            'description'           => '',
            'response_type'         => 'radio',
            'available_responses'   => [
                'Third party businesses',
                'Own bank accounts',
                'Individuals'
            ],
            'mandatory'             => true
        ],
        self::SIGNED_AGREEMENT_WITH_THIRD_PARTY => [
            'id'                    => self::SIGNED_AGREEMENT_WITH_THIRD_PARTY,
            'question'              => 'Please upload a copy of a signed agreement with the third party',
            'description'           => '',
            'response_type'         => 'file',
            'available_responses'   => [ ],
            'mandatory'             => false
        ],
        self::BUSINESS_MODEL => [
            'id'                    => self::BUSINESS_MODEL,
            'question'              => 'What is your business model and requirement?',
            'description'           => '',
            'response_type'         => 'textarea',
            'available_responses'   => [ ],
            'mandatory'             => true
        ],
        self::SAMPLE_PLANS => [
            'id'                    => self::SAMPLE_PLANS,
            'question'              => 'Sample plans',
            'description'           => '',
            'response_type'         => 'textarea',
            'available_responses'   => [ ],
            'mandatory'             => true
        ],
        self::WEBSITE_DETAILS => [
            'id'                    => self::WEBSITE_DETAILS,
            'question'              => 'Is website live? If yes, link to the page with more details',
            'description'           => '',
            'response_type'         => 'text',
            'available_responses'   => [ ],
            'mandatory'             => true
        ],
        self::EXPECTED_MONTHLY_REVENUE => [
            'id'                    => self::EXPECTED_MONTHLY_REVENUE,
            'question'              => 'Expected monthly revenue using this feature?',
            'description'           => '',
            'response_type'         => 'number',
            'available_responses'   => [ ],
            'mandatory'             => true
        ]
    ];

    public static $featureQuestionsMap = [
        FeatureConstants::MARKETPLACE => [
            self::USE_CASE,
            self::SETTLING_TO,
            self::SIGNED_AGREEMENT_WITH_THIRD_PARTY
        ],
        FeatureConstants::SUBSCRIPTIONS => [
            self::BUSINESS_MODEL,
            self::SAMPLE_PLANS,
            self::WEBSITE_DETAILS
        ],
        FeatureConstants::VIRTUAL_ACCOUNTS => [
            self::USE_CASE,
            self::EXPECTED_MONTHLY_REVENUE
        ]
    ];

    public static function getFeatureQuestions($featureName)
    {
        $response = [];
        if (key_exists($featureName, self::$featureQuestionsMap))
        {
            $questions = self::$featureQuestionsMap[$featureName];
            foreach ($questions as $question)
            {
                $response[$question] = self::$questionMap[$question];
            }
        }
        return $response;
    }
}
