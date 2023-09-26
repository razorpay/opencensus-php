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
        'Oauth_Custom Policy_Terms & Conditions'
    ];

    //TODO:: Change it back to 30 after data fix
    const DEFAULT_LAST_CRON_SUB_DAYS = 120;

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
            self::MANDATORY => true,
            self::PLATFORM  => self::PG
        ],
        'L2_privacy'                                   => [
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
            self::MANDATORY => true,
            self::PLATFORM  => self::PG
        ],
        'Oauth_App Policies' . '_' . MeConstants::TERMS             => [
            self::MANDATORY => true,
            self::PLATFORM  => self::PG
        ],
        'Oauth_RazorpayX Policies' . '_' . MeConstants::TERMS             => [
            self::MANDATORY => true,
            self::PLATFORM  => self::PG
        ],
        'Oauth_Custom Policy' . '_' . MeConstants::TERMS             => [
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
    ];

    const TEMPLATE_ID          = 'template_id';

    const METADATA             = 'metadata';

    const PARTNER_AUTH_TERMS   = 'PartnerAuth_Terms & Conditions';

    const SERVICE_AGREEMENT = 'Service Agreement';

    const TERMS_AND_CONDITIONS = 'Terms and Conditions';

    const L2_MILESTONE  = 'L2';

    const TERMS_OF_SERVICE = 'Terms of Service';

    const DOC_NAME = 'document_name';

    const OAUTH               = 'Oauth';
    const PARTNERSHIP         = 'Partnership';
    const PARTNER_TYPE_SWITCH = 'Partner_Type_Switch';
    const PARTNER_ACTIVATION  = 'PartnerActivation';


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
        ],

    ];

}
