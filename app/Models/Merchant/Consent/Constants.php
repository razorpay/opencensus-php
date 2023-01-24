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

    CONST X_SUBMISSION  = 'X';
    const L2_SUBMISSION = 'L2';

    //STATUS
    const PENDING   = 'pending';
    const INITIATED = 'initiated';
    const SUCCESS   = 'success';
    const FAILED    = 'failed';

    const STORE_CONSENTS_RETRY_PERIOD_IN_SEC            = 86400;
    const STORE_CONSENTS_ATTEMPT_COUNT_REDIS_KEY_PREFIX = 'store_consents_attempt_count';
    const STORE_CONSENTS_MAX_ATTEMPT                    = 3;
    const STORE_DOCUMENTS_ATTEMPT_COUNT                 = 'store_documents_attempt_count';

    const MERCHANT_MUTEX_LOCK_TIMEOUT                 = '60';
    const MERCHANT_MUTEX_RETRY_COUNT                  = '2';

    const VALID_LEGAL_DOC = [
        'L2_Terms and Conditions',
        'L2_Service Agreement',
        'L2_Privacy Policy',
        'L2_terms',
        'L2_privacy',
        'L2_agreement'
    ];

    const VALID_LEGAL_DOC_FOR_PARTNERSHIP = [
        'Partnership' . '_' . MeConstants::TERMS,
        'PartnerActivation' . '_' . MeConstants::TERMS,
        'PartnerActivation_Service Agreement',
        'PartnerActivation_Privacy Policy',
        'Oauth' . '_' . MeConstants::TERMS
    ];

    const VALID_LEGAL_DOC_FOR_X = [
        'X_Privacy Policy',
        'X_Terms of Use'
    ];

    const DEFAULT_LAST_CRON_SUB_DAYS  = 30;

    const WEBSITE      = 'website';
    const CONSENT_KEYS = self::WEBSITE . '_' . self::CONTACT_US . ',' .
                         self::WEBSITE . '_' . self::TERMS . ',' .
                         self::WEBSITE . '_' . self::REFUND . ',' .
                         self::WEBSITE . '_' . self::PRIVACY . ',' .
                         self::WEBSITE . '_' . self::SHIPPING;
}
