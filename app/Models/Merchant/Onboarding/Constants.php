<?php

namespace RZP\Models\Merchant\Onboarding;

use RZP\Models\Feature\Constants as FeatureConstants;

class Constants
{
    // Keys used to define the question names
    const BUSINESS_MODEL           = 'business_model';
    const EXPECTED_MONTHLY_REVENUE = 'expected_monthly_revenue';
    const ONBOARDING               = 'onboarding';
    const SETTLING_TO              = 'settling_to';
    const VENDOR_AGREEMENT         = 'vendor_agreement';
    const SAMPLE_PLANS             = 'sample_plans';
    const USE_CASE                 = 'use_case';
    const WEBSITE_DETAILS          = 'website_details';

    // Keys required to define each question referred by the above-mentioned constants
    const ID                  = 'id';
    const QUESTION            = 'question';
    const DESCRIPTION         = 'description';
    const RESPONSE_TYPE       = 'response_type';
    const AVAILABLE_RESPONSES = 'available_responses';
    const MANDATORY           = 'mandatory';

    // Constants used in API request params and responses
    const FEATURES = 'features';
    const MERCHANT = 'merchant';

    /*
     * Note: If the RESPONSE_TYPE is file, then,
     * a corresponding entry should be made in the class 'Models/Filestore/Type'
     */

    // Stores the details for each question irrespective of the feature that it belongs to
    public static $questionMap = [
        self::USE_CASE => [
            self::ID                  => self::USE_CASE,
            self::QUESTION            => 'What is your use case?',
            self::DESCRIPTION         => '',
            self::RESPONSE_TYPE       => 'textarea',
            self::AVAILABLE_RESPONSES => [],
            self::MANDATORY           => true
        ],

        self::SETTLING_TO => [
            self::ID                  => self::SETTLING_TO,
            self::QUESTION            => 'Who are you settling to?',
            self::DESCRIPTION         => '',
            self::RESPONSE_TYPE       => 'radio',
            self::AVAILABLE_RESPONSES => [
                'Third party businesses',
                'Own bank accounts',
                'Individuals'
            ],
            self::MANDATORY           => true
        ],

        self::VENDOR_AGREEMENT => [
            self::ID                  => self::VENDOR_AGREEMENT,
            self::QUESTION            => 'Please upload a copy of a signed agreement with the third party',
            self::DESCRIPTION         => '',
            self::RESPONSE_TYPE       => 'file',
            self::AVAILABLE_RESPONSES => [],
            self::MANDATORY           => false
        ],

        self::BUSINESS_MODEL => [
            self::ID                  => self::BUSINESS_MODEL,
            self::QUESTION            => 'What is your business model and requirement?',
            self::DESCRIPTION         => '',
            self::RESPONSE_TYPE       => 'textarea',
            self::AVAILABLE_RESPONSES => [],
            self::MANDATORY           => true
        ],

        self::SAMPLE_PLANS => [
            self::ID                  => self::SAMPLE_PLANS,
            self::QUESTION            => 'Sample plans',
            self::DESCRIPTION         => '',
            self::RESPONSE_TYPE       => 'textarea',
            self::AVAILABLE_RESPONSES => [],
            self::MANDATORY           => true
        ],

        self::WEBSITE_DETAILS => [
            self::ID                  => self::WEBSITE_DETAILS,
            self::QUESTION            => 'Is website live? If yes, link to the page with more details',
            self::DESCRIPTION         => '',
            self::RESPONSE_TYPE       => 'textarea',
            self::AVAILABLE_RESPONSES => [],
            self::MANDATORY           => true
        ],

        self::EXPECTED_MONTHLY_REVENUE => [
            self::ID                  => self::EXPECTED_MONTHLY_REVENUE,
            self::QUESTION            => 'Expected monthly revenue using this feature?',
            self::DESCRIPTION         => '',
            self::RESPONSE_TYPE       => 'number',
            self::AVAILABLE_RESPONSES => [],
            self::MANDATORY           => true
        ]
    ];

    // Stores the mapping of the features to their corresponding questions
    public static $featureQuestionsMap = [
        FeatureConstants::MARKETPLACE => [
            self::USE_CASE,
            self::SETTLING_TO,
            self::VENDOR_AGREEMENT
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

    /**
     * Returns a nested structure of the questions for the feature param passed along
     * with all the details for each question
     *
     * @param string $featureName
     *
     * @return array
     */
    public static function getFeatureQuestions(string $featureName)
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
