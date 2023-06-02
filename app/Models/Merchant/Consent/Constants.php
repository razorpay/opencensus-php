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

    const STORE_CONSENTS_RETRY_PERIOD_IN_SEC            = 86400;
    const STORE_CONSENTS_ATTEMPT_COUNT_REDIS_KEY_PREFIX = 'store_consents_attempt_count';
    const STORE_CONSENTS_MAX_ATTEMPT                    = 3;
    const STORE_DOCUMENTS_ATTEMPT_COUNT                 = 'store_documents_attempt_count';

    const MERCHANT_MUTEX_LOCK_TIMEOUT = '60';
    const MERCHANT_MUTEX_RETRY_COUNT  = '2';

    const VALID_LEGAL_DOC_L2 = [
        'L2_Terms and Conditions',
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

    //TODO:: Change it back to 30 after data fix
    const DEFAULT_LAST_CRON_SUB_DAYS = 120;

    const WEBSITE      = 'website';
    const CONSENT_KEYS = self::WEBSITE . '_' . self::CONTACT_US . ',' .
                         self::WEBSITE . '_' . self::TERMS . ',' .
                         self::WEBSITE . '_' . self::REFUND . ',' .
                         self::WEBSITE . '_' . self::PRIVACY . ',' .
                         self::WEBSITE . '_' . self::SHIPPING . ',' .
                         self::VALID_LEGAL_DOC_KEYS;

    const VALID_LEGAL_DOC_KEYS = 'L2_Terms and Conditions' . ',' .
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
                                 self::PARTNER_AUTH_TERMS;

    const VALID_LEGAL_DOC = [
        'L2_Terms and Conditions'                      => [
            self::MANDATORY => true,
            self::PLATFORM  => self::PG
        ],
        'L2_Service Agreement'                         => [
            self::MANDATORY => true,
            self::PLATFORM  => self::PG
        ],
        'L2_Privacy Policy'                            => [
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
    ];

    const PARTNER_AUTH_TERMS = 'PartnerAuth_Terms & Conditions';
}
