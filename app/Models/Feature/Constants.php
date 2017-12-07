<?php

namespace RZP\Models\Feature;

use RZP\Models\Merchant\Detail as MerchantDetail;

class Constants
{
    const ENTITY_IDS                    = 'entity_ids';
    const NAMES                         = 'names';
    const DUMMY                         = 'dummy';
    const WEBHOOKS                      = 'webhooks';
    const AGGREGATOR                    = 'aggregator';
    const TOKENS                        = 'tokens';
    const S2SWALLET                     = 's2swallet';
    const S2SUPI                        = 's2supi';
    const S2SAEPS                       = 's2saeps';
    const SETL_REPORT                   = 'setl_report';
    const NOFLASHCHECKOUT               = 'noflashcheckout';
    const RECURRING                     = 'recurring';
    const S2S                           = 's2s';
    const INVOICE                       = 'invoice';
    const NOZEROPRICING                 = 'nozeropricing';
    const REVERSE                       = 'reverse';
    const BROKING_REPORT                = 'broking_report';
    const DSP_REPORT                    = 'dsp_report';
    const RPP_REPORT                    = 'rpp_report';
    const AGGREGATOR_REPORT             = 'aggregator_report';
    const PAYMENT_EMAIL_FETCH           = 'payment_email_fetch';
    const CREATED_FLOW                  = 'created_flow';
    const PAYOUT                        = 'payout';
    const OPENWALLET                    = 'openwallet';
    const MARKETPLACE                   = 'marketplace';
    const EMAIL_OPTIONAL                = 'email_optional';
    const CONTACT_OPTIONAL              = 'contact_optional';
    const SUBSCRIPTIONS                 = 'subscriptions';
    const ZOHO                          = 'zoho';
    const EXPOSE_DOWNTIMES              = 'expose_downtimes';
    const PAYMENT_FAILURE_EMAIL         = 'payment_failure_email';
    const VIRTUAL_ACCOUNTS              = 'virtual_accounts';
    const INVOICE_PARTIAL_PAYMENTS      = 'invoice_partial_payments';
    const HIDE_DOWNTIMES                = 'hide_downtimes';
    const OLD_CREDITS_FLOW              = 'old_credits_flow';
    const CHARGE_AT_WILL                = 'charge_at_will';
    const E_MANDATE                     = 'e_mandate';
    const EMI_MERCHANT_SUBVENTION       = 'emi_merchant_subvention';
    const FSS_RISK_UDF                  = 'fss_risk_udf';
    const RULE_FILTER                   = 'rule_filter';
    const TPV                           = 'tpv';
    const IRCTC_REPORT                  = 'irctc_report';
    const DISABLE_MAESTRO               = 'disable_maestro';
    const DISABLE_RUPAY                 = 'disable_rupay';
    const BLOCK_INTERNATIONAL_RECURRING = 'block_intl_recurring';
    const BHARAT_QR                     = 'bharat_qr';
    const MOBIKWIK_OFFERS               = 'mobikwik_offers';
    const ALLOW_DC_RECURRING            = 'allow_dc_recurring';
    const SKIP_HOLD_FUNDS_ON_PAYOUT     = 'skip_hold_funds_on_payout';
    const REPORT_V2                     = 'report_v2';


    // Different actions for feature activation flow
    const CREATE           = 'create';
    const UPDATE           = 'update';

    // TODO: Use this instead of allFeatures once in final code change pr
    public static $featureValueMap = [
        self::DUMMY                         => true,
        self::WEBHOOKS                      => true,
        self::AGGREGATOR                    => true,
        self::TOKENS                        => true,
        self::S2SWALLET                     => true,
        self::S2SUPI                        => true,
        self::S2SAEPS                       => true,
        self::NOFLASHCHECKOUT               => true,
        self::RECURRING                     => true,
        self::S2S                           => true,
        self::INVOICE                       => true,
        self::NOZEROPRICING                 => false,
        self::REVERSE                       => true,
        self::BROKING_REPORT                => true,
        self::DSP_REPORT                    => true,
        self::RPP_REPORT                    => true,
        self::AGGREGATOR_REPORT             => true,
        self::PAYMENT_EMAIL_FETCH           => true,
        self::CREATED_FLOW                  => true,
        self::PAYOUT                        => true,
        self::OPENWALLET                    => true,
        self::MARKETPLACE                   => true,
        self::EMAIL_OPTIONAL                => true,
        self::CONTACT_OPTIONAL              => true,
        self::SUBSCRIPTIONS                 => true,
        self::ZOHO                          => true,
        self::EXPOSE_DOWNTIMES              => true,
        self::PAYMENT_FAILURE_EMAIL         => true,
        self::VIRTUAL_ACCOUNTS              => true,
        self::INVOICE_PARTIAL_PAYMENTS      => true,
        self::HIDE_DOWNTIMES                => true,
        self::OLD_CREDITS_FLOW              => true,
        self::CHARGE_AT_WILL                => true,
        self::E_MANDATE                     => true,
        self::EMI_MERCHANT_SUBVENTION       => true,
        self::FSS_RISK_UDF                  => true,
        self::RULE_FILTER                   => true,
        self::TPV                           => true,
        self::IRCTC_REPORT                  => true,
        self::DISABLE_MAESTRO               => true,
        self::DISABLE_RUPAY                 => true,
        self::BLOCK_INTERNATIONAL_RECURRING => false,
        self::BHARAT_QR                     => true,
        self::MOBIKWIK_OFFERS               => true,
        self::ALLOW_DC_RECURRING            => true,
        self::SKIP_HOLD_FUNDS_ON_PAYOUT     => true,
        self::REPORT_V2                     => true,
    ];

    // Keys used in the feature on-boarding workflow
    const STATUS                        = 'status';
    const PRODUCT                       = 'product';
    const FEATURES                      = 'features';
    const MERCHANT                      = 'merchant';
    const ONBOARDING                    = 'onboarding';
    const ONBOARDING_SUBMISSIONS_FETCH  = 'onboarding_submissions_fetch';
    const ONBOARDING_SUBMISSIONS_UPSERT = 'onboarding_submissions_upsert';

    // Keys used to define the question names in the on-boarding process
    const BUSINESS_MODEL           = 'business_model';
    const EXPECTED_MONTHLY_REVENUE = 'expected_monthly_revenue';
    const SETTLING_TO              = 'settling_to';
    const VENDOR_AGREEMENT         = 'vendor_agreement';
    const SAMPLE_PLANS             = 'sample_plans';
    const USE_CASE                 = 'use_case';
    const WEBSITE_DETAILS          = 'website_details';

    // Keys that will describe the above-mentioned questions
    const ID                  = 'id';
    const QUESTION            = 'question';
    const DESCRIPTION         = 'description';
    const RESPONSE_TYPE       = 'response_type';
    const AVAILABLE_RESPONSES = 'available_responses';
    const MANDATORY           = 'mandatory';

    const ONBOARDING_STATUSES = [
        MerchantDetail\Entity::PENDING,
        MerchantDetail\Entity::REJECTED,
        MerchantDetail\Entity::APPROVED,
    ];

    /**
     * Features that are exposed to the merchant and can be
     * enabled/disabled
     *
     * @var array
     */
    public static $visibleFeaturesMap = [
        self::NOFLASHCHECKOUT  => [
            'feature'       => self::NOFLASHCHECKOUT,
            'display_name'  => 'No Flash Checkout',
            'documentation' => ''
        ],
        self::MARKETPLACE      => [
            'feature'       => self::MARKETPLACE,
            'display_name'  => 'Route',
            'documentation' => 'route'
        ],
        self::SUBSCRIPTIONS    => [
            'feature'       => self::SUBSCRIPTIONS,
            'display_name'  => 'Subscriptions',
            'documentation' => 'subscriptions'
        ],
        self::VIRTUAL_ACCOUNTS => [
            'feature'       => self::VIRTUAL_ACCOUNTS,
            'display_name'  => 'Smart Collect',
            'documentation' => 'smart-collect'
        ],
    ];

    /*
     * PRODUCT_FEATURES should be a subset of the visible features.
     * If any of these features are enabled on live mode, the user
     * will be notified through an email.
     * Product features can be enabled/disabled on test mode by the merchant,
     * but not on the live mode.
     */
    const PRODUCT_FEATURES = [
        self::MARKETPLACE,
        self::SUBSCRIPTIONS,
        self::VIRTUAL_ACCOUNTS
    ];

    /**
     * Note: If the RESPONSE_TYPE is file, then,
     * a corresponding entry should be made in the class 'Models/Filestore/Type'
     */

    /**
     * Stores the details for each question irrespective of the feature that it belongs to.
     * TODO: These constants can be moved into separate constants file for questions
     */
    public static $questionMap = [
        self::USE_CASE => [
            self::QUESTION            => 'What is your use case?',
            self::DESCRIPTION         => '',
            self::RESPONSE_TYPE       => 'textarea',
            self::AVAILABLE_RESPONSES => [],
            self::MANDATORY           => true
        ],

        self::SETTLING_TO => [
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
            self::QUESTION            => 'Please upload a copy of a signed agreement with the third party',
            self::DESCRIPTION         => '',
            self::RESPONSE_TYPE       => 'file',
            self::AVAILABLE_RESPONSES => [],
            self::MANDATORY           => false
        ],

        self::BUSINESS_MODEL => [
            self::QUESTION            => 'What is your business model and requirement?',
            self::DESCRIPTION         => '',
            self::RESPONSE_TYPE       => 'textarea',
            self::AVAILABLE_RESPONSES => [],
            self::MANDATORY           => true
        ],

        self::SAMPLE_PLANS => [
            self::QUESTION            => 'Sample plans',
            self::DESCRIPTION         => '',
            self::RESPONSE_TYPE       => 'textarea',
            self::AVAILABLE_RESPONSES => [],
            self::MANDATORY           => true
        ],

        self::WEBSITE_DETAILS => [
            self::QUESTION            => 'Is website live? If yes, link to the page with more details',
            self::DESCRIPTION         => '',
            self::RESPONSE_TYPE       => 'text',
            self::AVAILABLE_RESPONSES => [],
            self::MANDATORY           => true
        ],

        self::EXPECTED_MONTHLY_REVENUE => [
            self::QUESTION            => 'Expected monthly revenue using this feature?',
            self::DESCRIPTION         => '',
            self::RESPONSE_TYPE       => 'number',
            self::AVAILABLE_RESPONSES => [],
            self::MANDATORY           => true
        ]
    ];

    /**
     * Stores the mapping of the features to their corresponding questions
     */
    public static $featureQuestionsMap = [
        self::MARKETPLACE => [
            self::USE_CASE,
            self::SETTLING_TO,
            self::VENDOR_AGREEMENT
        ],

        self::SUBSCRIPTIONS => [
            self::BUSINESS_MODEL,
            self::SAMPLE_PLANS,
            self::WEBSITE_DETAILS
        ],

        self::VIRTUAL_ACCOUNTS => [
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
    public static function getFeatureQuestions(string $featureName): array
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

    public static function getFeatureValue($featureName): bool
    {
        return self::$featureValueMap[$featureName];
    }
}
