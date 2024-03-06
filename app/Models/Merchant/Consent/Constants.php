<?php


namespace RZP\Models\Merchant\Consent;

use RZP\Models\Merchant\Constants as MeConstants;

class Constants
{
    const INPUT       = 'input';
    const IP          = 'ip';
    const USER_AGENT  = 'user_agent';
    const BASIC_AUTH  = 'basicauth';
    const REQUEST     = 'request';
    const REQUEST_CTX = 'request.ctx';
    const CONTACT_US  = 'contact_us';
    const TERMS       = 'terms';
    const REFUND      = 'refund';
    const PRIVACY     = 'privacy';
    const SHIPPING    = 'shipping';
    const IP_ADDRESS  = 'ip_address';
    const PG          = 'pg';
    const RX          = 'rx';

    const PARTNER_ID       = 'partner_id';
    const PARTNER_NAME     = 'partner_name';
    const APPLICATION_ID   = 'application_id';
    const APPLICATION_NAME = 'application_name';

    const EMAIL_PARAMS = 'email_params';

    const X_SUBMISSION  = 'X';
    const L2_SUBMISSION = 'L2';

    //STATUS
    const PENDING   = 'pending';
    const INITIATED = 'initiated';
    const SUCCESS   = 'success';
    const FAILED    = 'failed';

    const MANDATORY = 'mandatory';
    const PLATFORM  = 'platform';

    const SCOPE_POLICIES = 'scope_policies';

    const CUSTOM_POLICY = 'Custom Policy';

    const STORE_CONSENTS_RETRY_PERIOD_IN_SEC            = 86400;
    const STORE_CONSENTS_ATTEMPT_COUNT_REDIS_KEY_PREFIX = 'store_consents_attempt_count';
    const STORE_CONSENTS_MAX_ATTEMPT                    = 3;
    const STORE_DOCUMENTS_ATTEMPT_COUNT                 = 'store_documents_attempt_count';

    const MERCHANT_MUTEX_LOCK_TIMEOUT = '60';
    const MERCHANT_MUTEX_RETRY_COUNT  = '2';

    const VALID_LEGAL_DOC_L2 = [
        'L2_Terms and Conditions',
        'L2_Terms of Service',
        'L2_Service Agreement',
        'L2_Privacy Policy',
        'L2_terms',
        'L2_privacy',
        'L2_agreement'
    ];

    const VALID_LEGAL_DOC_BANKING_ORG = [
        'L2_Terms and Conditions',
        'L2_Privacy Policy',
        'L2_Service Agreement',
    ];

    const VALID_LEGAL_DOC_OAUTH = [
        'Oauth_App Policies_Terms & Conditions',
        'Oauth_RazorpayX Policies_Terms & Conditions',
        'Oauth_Terms & Conditions',
        'Oauth_Custom Policy_Terms & Conditions',
        'Oauth_App Policy_Terms & Conditions',
        'Oauth_RazorpayX App Policy_Terms & Conditions',
        'Oauth_Platform Partnerships Policy_Terms & Conditions'
    ];

    const PARTNERSHIP_MILESTONES_WITH_APP_POLICIES = [
        self::OAUTH,
        self::PARTNER_AUTH
    ];

    //TODO:: Change it back to 30 after data fix
    const DEFAULT_LAST_CRON_SUB_DAYS = 120;

    const DEFAULT_LAST_ALERT_SUB_DAYS = 1;

    const WEBSITE      = 'website';
    const CONSENT_KEYS = self::WEBSITE . '_' . self::CONTACT_US . ',' .
                         self::WEBSITE . '_' . self::TERMS . ',' .
                         self::WEBSITE . '_' . self::REFUND . ',' .
                         self::WEBSITE . '_' . self::PRIVACY . ',' .
                         self::WEBSITE . '_' . self::SHIPPING . ',' .
                         self::VALID_LEGAL_DOC_KEYS;

    const VALID_LEGAL_DOC_KEYS = 'L2_Terms of Service' . ',' .
                                 'L2_Terms and Conditions' . ',' .
                                 'L2_Service Agreement' . ',' .
                                 'L2_Privacy Policy' . ',' .
                                 'L2_terms' . ',' .
                                 'L2_privacy' . ',' .
                                 'L2_agreement' . ',' .
                                 'DIGILOCKER_TERMS_AND_CONDITIONS' . ',' .
                                 'EasyKycSubMerchant_Terms of Service' . ',' .
                                 'EasyKycSubMerchant' . '_' . MeConstants::TERMS . ',' .
                                 'EasyKycSubMerchant_Service Agreement' . ',' .
                                 'EasyKycSubMerchant_Privacy Policy' . ',' .
                                 'Partnership' . '_' . MeConstants::TERMS . ',' .
                                 'Partner_Type_Switch' . '_' . MeConstants::TERMS . ',' .
                                 'PartnerActivation' . '_' . MeConstants::TERMS . ',' .
                                 'PartnerActivation_Service Agreement' . ',' .
                                 'PartnerActivation_Privacy Policy' . ',' .
                                 'Oauth' . '_' . MeConstants::TERMS . ',' .
                                 'X_Privacy Policy' . ',' .
                                 'X_Terms of Use' . ',' .
                                 'Oauth_App Policies' . '_' . MeConstants::TERMS . ',' .
                                 'Oauth_RazorpayX Policies' . '_' . MeConstants::TERMS . ',' .
                                 'Oauth_Custom Policy' . '_' . MeConstants::TERMS . ',' .
                                 'Oauth_App Policy' . '_' . MeConstants::TERMS . ',' .
                                 'Oauth_RazorpayX App Policy' . '_' . MeConstants::TERMS . ',' .
                                 'Oauth_Platform Partnerships Policy' . '_' . MeConstants::TERMS . ',' .
                                 self::PARTNER_AUTH_TERMS;

    const VALID_LEGAL_DOC = [
        'L2_Terms of Service'                      => [
            self::DOC_NAME  => 'Terms of Service',
            self::MANDATORY => true,
            self::PLATFORM  => self::PG
        ],
        'L2_Terms and Conditions'                      => [
            self::DOC_NAME  => 'Terms of Service',
            self::MANDATORY => true,
            self::PLATFORM  => self::PG
        ],
        'L2_Service Agreement'                         => [
            self::DOC_NAME  => 'Service Agreement',
            self::MANDATORY => true,
            self::PLATFORM  => self::PG
        ],
        'L2_Privacy Policy'                            => [
            self::DOC_NAME  => 'Privacy Policy',
            self::MANDATORY => true,
            self::PLATFORM  => self::PG
        ],
        'L2_terms'                                     => [
            self::DOC_NAME  => 'Terms of Service',
            self::MANDATORY => true,
            self::PLATFORM  => self::PG
        ],
        'L2_privacy'                                   => [
            self::DOC_NAME  => 'Privacy Policy',
            self::MANDATORY => true,
            self::PLATFORM  => self::PG
        ],
        'L2_agreement'                                 => [
            self::MANDATORY => true,
            self::PLATFORM  => self::PG
        ],
        'DIGILOCKER_TERMS_AND_CONDITIONS'              => [
            self::MANDATORY => true,
            self::PLATFORM  => "pg"
        ],
        'Partnership' . '_' . MeConstants::TERMS       => [
            self::MANDATORY => true,
            self::PLATFORM  => self::PG
        ],
        'Partner_Type_Switch' . '_' . MeConstants::TERMS       => [
            self::MANDATORY => true,
            self::PLATFORM  => self::PG
        ],
        'PartnerActivation' . '_' . MeConstants::TERMS => [
            self::MANDATORY => true,
            self::PLATFORM  => self::PG
        ],
        'PartnerActivation_Service Agreement'          => [
            self::MANDATORY => true,
            self::PLATFORM  => self::PG
        ],
        'PartnerActivation_Privacy Policy'             => [
            self::MANDATORY => true,
            self::PLATFORM  => self::PG
        ],
        'Oauth' . '_' . MeConstants::TERMS             => [
            self::DOC_NAME  => 'App Policy',
            self::MANDATORY => true,
            self::PLATFORM  => self::PG
        ],
        'X_Privacy Policy'                             => [
            self::MANDATORY => true,
            self::PLATFORM  => self::RX
        ],
        'X_Terms of Use'                               => [
            self::MANDATORY => true,
            self::PLATFORM  => self::RX
        ],
        self::PARTNER_AUTH_TERMS                       => [
            self::DOC_NAME  => 'App Policy',
            self::MANDATORY => true,
            self::PLATFORM  => self::PG
        ],
        'Oauth_App Policies' . '_' . MeConstants::TERMS             => [
            self::DOC_NAME  => 'App Policy',
            self::MANDATORY => true,
            self::PLATFORM  => self::PG
        ],
        'Oauth_RazorpayX Policies' . '_' . MeConstants::TERMS             => [
            self::DOC_NAME  => 'RazorpayX App Policy',
            self::MANDATORY => true,
            self::PLATFORM  => self::PG
        ],
        'Oauth_Custom Policy' . '_' . MeConstants::TERMS             => [
            self::DOC_NAME  => 'Platform Partnerships Policy',
            self::MANDATORY => true,
            self::PLATFORM  => self::PG
        ],
        'Oauth_App Policy' . '_' . MeConstants::TERMS                     => [
            self::DOC_NAME  => 'App Policy',
            self::MANDATORY => true,
            self::PLATFORM  => self::PG
        ],
        'Oauth_RazorpayX App Policy' . '_' . MeConstants::TERMS           => [
            self::DOC_NAME  => 'RazorpayX App Policy',
            self::MANDATORY => true,
            self::PLATFORM  => self::PG
        ],
        'Oauth_Platform Partnerships Policy' . '_' . MeConstants::TERMS   => [
            self::DOC_NAME  => 'Platform Partnerships Policy',
            self::MANDATORY => true,
            self::PLATFORM  => self::PG
        ],
        'EasyKycSubMerchant_Terms of Service'                      => [
            self::DOC_NAME  => 'Terms of Service',
            self::MANDATORY => true,
            self::PLATFORM  => self::PG
        ],
        'EasyKycSubMerchant' . '_' . MeConstants::TERMS                      => [
            self::DOC_NAME  => 'Terms of Service',
            self::MANDATORY => true,
            self::PLATFORM  => self::PG
        ],
        'EasyKycSubMerchant_Service Agreement'                         => [
            self::DOC_NAME  => 'Service Agreement',
            self::MANDATORY => true,
            self::PLATFORM  => self::PG
        ],
        'EasyKycSubMerchant_Privacy Policy'                            => [
            self::DOC_NAME  => 'Privacy Policy',
            self::MANDATORY => true,
            self::PLATFORM  => self::PG
        ],
    ];

    const TEMPLATE_ID_MAPPING = [
        'https://razorpay.com/privacy/'                                 => 'merchant_consent_privacy_template_id',
        'https://razorpay.com/terms/'                                   => 'merchant_consent_terms_template_id',
        'https://razorpay.com/s/terms/partners/'                        => 'partnership_consent_terms_template_id',
        'https://razorpay.com/terms/razorpayx/partnership/'             => 'partnership_consent_oauth_template_id',
        'https://razorpay.com/s/terms/partners/aggregator-and-platform' => 'partnership_consent_switch_template_id',
        "https://razorpay.com/s/terms/partners/payments-oauth/read-only"       => 'partnership_oauth_consent_read_only_template_id',
        "https://razorpay.com/s/terms/partners/payments-oauth/read-and-write/" => 'partnership_oauth_consent_read_write_template_id',
        "https://razorpay.com/s/terms/partners/payments/aggregator-partner/"   => 'partnership_aggregator_consent_template_id',
    ];

    const TEMPLATE_ID          = 'template_id';

    const METADATA             = 'metadata';

    const PARTNER_AUTH         = 'PartnerAuth';

    const PARTNER_AUTH_TERMS   = self::PARTNER_AUTH .'_App Policy_Terms & Conditions';

    const SERVICE_AGREEMENT = 'Service Agreement';

    const TERMS_AND_CONDITIONS = 'Terms and Conditions';

    const L2_MILESTONE  = 'L2';

    const TERMS_OF_SERVICE = 'Terms of Service';

    const DOC_NAME = 'document_name';

    const OAUTH               = 'Oauth';
    const PARTNERSHIP         = 'Partnership';
    const PARTNER_TYPE_SWITCH = 'Partner_Type_Switch';
    const PARTNER_ACTIVATION  = 'PartnerActivation';

    const EASY_KYC_ACCESS_SUBMERCHANT = 'EasyKycSubMerchant';

    const PARTNER_DOMAIN_CONSENT_DETAILS = [
        'email' => [
            self::PARTNERSHIP         => [
                'template_name'      => 'email.partnerships_experience.consent_update_partner_type',
                'template_namespace' => 'partnerships-experience',
                'subject'            => 'Razorpay Partner Program: Our Terms of Service and Privacy Policy'
            ],
            self::PARTNER_TYPE_SWITCH => [
                'template_name'      => 'email.partnerships_experience.consent_partner_type_switch',
                'template_namespace' => 'partnerships-experience',
                'subject'            => 'Razorpay Partner Program: Our Terms of Service and Privacy Policy'
            ],
            self::PARTNER_ACTIVATION  => [
                'template_name'      => 'email.partnerships_experience.consent_partner_activation',
                'template_namespace' => 'partnerships-experience',
                'subject'            => 'Razorpay Partner Program: Our Terms of Service and Privacy Policy'
            ],
            self::OAUTH               => [
                'template_name'      => 'email.partnerships.consent.tnc_app_policy_oauth',
                'template_namespace' => 'partnerships',
                'subject'            => 'Razorpay: App Policy'
            ],
            self::PARTNER_AUTH        => [
                'template_name'      => 'email.partnerships.consent.tnc_app_policy_aggregator_partner',
                'template_namespace' => 'partnerships',
                'subject'            => 'Razorpay: App Policy'
            ],
        ],

    ];

    const ONBOARDING_APIS_CONSENT_NAME_MAPPING = [
        'terms'   => 'Terms of Service',
        'privacy' => 'Privacy Policy'
    ];

    const OAUTH_POLICY_TO_CONSENT_NAME_MAPPING = [
        'App Policies'       => 'App Policy',
        'RazorpayX Policies' => 'RazorpayX App Policy',
        'Custom Policy'      => 'Platform Partnerships Policy'
    ];

    // This mapping is used to update older sub-merchant consents where the consent type mismatched its name.
    const SUBMERCHANT_CONSENTS_TO_NAME_MAPPING = [
        'Oauth_App Policies_Terms & Conditions'         => 'App Policy',
        'Oauth_RazorpayX Policies_Terms & Conditions'   => 'RazorpayX App Policy',
        'Oauth_Terms & Conditions'                      => 'App Policy',
        'Oauth_Custom Policy_Terms & Conditions'        => 'Platform Partnerships Policy',
        'L2_terms'                                      => 'Terms of Service',
        'L2_privacy'                                    => 'Privacy Policy',
    ];
}
