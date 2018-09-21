<?php

namespace RZP\Constants;

/**
 * Shield Constants
 */
final class Shield
{
    // Request payload constants
    const MERCHANT_ID             = 'merchant_id';
    const ENTITY_TYPE             = 'entity_type';
    const ENTITY_ID               = 'entity_id';
    const INPUT                   = 'input';
    const MERCHANT_NAME           = 'merchant_name';
    const MERCHANT_CATEGORY       = 'merchant_category';
    const MERCHANT_RISK_THRESHOLD = 'merchant_risk_threshold';
    const MERCHANT_WEBSITE        = 'merchant_website';
    const ID                      = 'id';
    const AMOUNT                  = 'amount';
    const CURRENCY                = 'currency';
    const RECURRING               = 'recurring';
    const CONTACT                 = 'contact';
    const INTERNATIONAL           = 'international';
    const EMAIL                   = 'email';
    const METHOD                  = 'method';
    const BANK                    = 'bank';
    const WALLET                  = 'wallet';
    const VPA                     = 'vpa';
    const CARD_IIN                = 'card_iin';
    const CARD_NETWORK            = 'card_network';
    const CARD_TYPE               = 'card_type';
    const CARD_COUNTRY            = 'card_country';
    const CARD_ISSUER             = 'card_issuer';
    const CARD_NAME               = 'card_name';
    const CARD_LAST4              = 'card_last4';
    const CARD_LENGTH             = 'card_length';
    const CARD_EXPIRY_MONTH       = 'card_expiry_month';
    const CARD_EXPIRY_YEAR        = 'card_expiry_year';
    const IP                      = 'ip';
    const USER_AGENT              = 'user_agent';
    const REFERER                 = 'referer';
    const BROWSER                 = 'browser';
    const BROWSER_VERSION         = 'browser_version';
    const OS                      = 'os';
    const OS_VERSION              = 'os_version';
    const DEVICE                  = 'device';
    const PLATFORM                = 'platform';
    const PLATFORM_VERSION        = 'platform_version';
    const LIBRARY                 = 'library';
    const LIBRARY_VERSION         = 'library_version';
    const ATTEMPTS                = 'attempts';
    const ACCEPT_LANGUAGE         = 'accept_language';
    const CREATED_AT              = 'created_at';
    const DEFAULT_EMAIL           = 'void@razorpay.com';
    const DEFAULT_ACCEPT_LANGUAGE = 'en-US';

    // Response constants
    const ACTION_KEY              = 'action';
    const ACTION_ALLOW            = 'allow';
    const ACTION_REVIEW           = 'review';
    const ACTION_BLOCK            = 'block';
}
