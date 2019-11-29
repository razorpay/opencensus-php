<?php

namespace RZP\Models\Merchant;

/**
 * General constants for Merchant Model.
 */
final class Constants
{
    const INDIVIDUAL                              = 'individual';
    const CONTACT                                 = 'contact';
    const TIMESTAMP                               = 'timestamp';
    const REF                                     = 'ref';
    const DATE                                    = 'date';
    const SIGNUP_DATE                             = 'signup_date';
    const SUSPEND                                 = 'suspend';
    const UNSUSPEND                               = 'unsuspend';

    // Used for pagination in submerchant listing for partners
    const TO                                      = 'to';
    const FROM                                    = 'from';
    const SKIP                                    = 'skip';
    const COUNT                                   = 'count';

    // Partner constants
    const BANK                                    = 'bank';
    const PARTNER                                 = 'partner';
    const RESELLER                                = 'reseller';
    const AGGREGATOR                              = 'aggregator';
    const FULLY_MANAGED                           = 'fully_managed';
    const PURE_PLATFORM                           = 'pure_platform';
    const PARTNER_INTENT                          = 'partner_intent';
    const TRANSLATE_WEBHOOK_URL                   = 'translate_webhook_url';

    const DOCUMENT_TYPE                           = 'document_type';

    // Used in partners flows
    const APPLICATION_ID                          = 'application_id';

    // used in merchant es sync
    const INTERVAL                                 = 'interval';
    const RECORDS_PROCESSED                        = 'records_processed';

    const REMOVE                                  = 'remove';
    const LIVE                                    = 'live';

    public static $partnerTypes = [
        self::BANK,
        self::RESELLER,
        self::AGGREGATOR,
        self::FULLY_MANAGED,
        self::PURE_PLATFORM,
    ];

    // Used in merchant activation elastic search flows
    const INSTANT_ACTIVATION = 'instant_activation';

    // Used in elastic search merchant search flows
    const BUSINESS_TYPE_BUCKET = 'business_type_bucket';

    const IS_WHITELISTED_ACTIVATION = 'is_whitelisted_activation';

    // need clarification constants
    const REASON_TYPE            = 'reason_type';
    const FIELD_TYPE             = 'field_type';
    const FIELD_VALUE            = 'field_value';
    const FIELD_NAME             = 'field_name';
    const REASON                 = 'reason';
    const REASON_CODE            = 'reason_code';
    const CUSTOM_REASON_TYPE     = 'custom';
    const PREDEFINED_REASON_TYPE = 'predefined';

    /**
     * Partner types that are allowed to view and manage
     * partner settings like client creds.
     *
     * @var array
     */
    public static $settingsAccessPartnerTypes = [
        self::FULLY_MANAGED,
        self::AGGREGATOR,
    ];

    /**
     * Partner types that are allowed to view and manage webhooks.
     *
     * @var array
     */
    public static $webhooksAccessPartnerTypes = [
        self::FULLY_MANAGED,
        self::AGGREGATOR,
        self::PURE_PLATFORM,
    ];

    /**
     * Step Map gives information on attributes filled by merchant Step wise.
     * this is used to let merchant know what all the steps are finished and
     * can continue from where merchant left the activation form.
     */
    const STEP_MAP = [
        Detail\Entity::CONTACT_NAME                => 1,
        Detail\Entity::CONTACT_EMAIL               => 1,
        Detail\Entity::CONTACT_MOBILE              => 1,

        Detail\Entity::BUSINESS_TYPE               => 2,
        Detail\Entity::BUSINESS_NAME               => 2,
        Detail\Entity::BUSINESS_DBA                => 2,
        Detail\Entity::BUSINESS_INTERNATIONAL      => 2,
        Detail\Entity::BUSINESS_REGISTERED_ADDRESS => 2,
        Detail\Entity::BUSINESS_REGISTERED_STATE   => 2,
        Detail\Entity::BUSINESS_REGISTERED_CITY    => 2,
        Detail\Entity::BUSINESS_REGISTERED_PIN     => 2,
        Detail\Entity::BUSINESS_OPERATION_ADDRESS  => 2,
        Detail\Entity::BUSINESS_OPERATION_STATE    => 2,
        Detail\Entity::BUSINESS_OPERATION_CITY     => 2,
        Detail\Entity::BUSINESS_OPERATION_PIN      => 2,
        Detail\Entity::GSTIN                       => 2,
        Detail\Entity::P_GSTIN                     => 2,
        Detail\Entity::PROMOTER_PAN                => 2,
        Detail\Entity::PROMOTER_PAN_NAME           => 2,

        Detail\Entity::BANK_BRANCH_IFSC            => 3,
        Detail\Entity::BANK_ACCOUNT_NUMBER         => 3,
        Detail\Entity::BANK_ACCOUNT_NAME           => 3,

        Detail\Entity::BUSINESS_PROOF_URL          => 4,
        Detail\Entity::BUSINESS_PAN_URL            => 4,
        Detail\Entity::ADDRESS_PROOF_URL           => 4,
        Detail\Entity::PROMOTER_ADDRESS_URL        => 4,
        Detail\Entity::FORM_12A_URL                => 4,
        Detail\Entity::FORM_80G_URL                => 4,
    ];

    const STEP_MAP_ACCOUNT = [
        Detail\Entity::BUSINESS_TYPE               => 1,
        Detail\Entity::BUSINESS_NAME               => 1,
        Detail\Entity::COMPANY_PAN                 => 1,
        Detail\Entity::PROMOTER_PAN                => 1,

        Detail\Entity::BANK_BRANCH_IFSC            => 2,
        Detail\Entity::BANK_ACCOUNT_NUMBER         => 2,
        Detail\Entity::BANK_ACCOUNT_NAME           => 2,

        Detail\Entity::ADDRESS_PROOF_URL           => 3,
        Detail\Entity::PROMOTER_PAN_URL            => 3,
    ];

    const UPLOAD_KEYS = [
        Detail\Entity::BUSINESS_PROOF_URL   => 'business_proof',
        Detail\Entity::BUSINESS_PAN_URL     => 'business_pan_proof',
        Detail\Entity::ADDRESS_PROOF_URL    => 'address_proof',
        Detail\Entity::PROMOTER_ADDRESS_URL => 'promoter_address_proof',
        Detail\Entity::FORM_12A_URL         => 'form_12a_url',
        Detail\Entity::FORM_80G_URL         => 'form_80g_url',
    ];

    const UPLOAD_KEYS_ACCOUNT = [
        Detail\Entity::ADDRESS_PROOF_URL    => 'address_proof',
        Detail\Entity::PROMOTER_PAN_URL     => 'promoter_pan_proof',
    ];

    const PRE_SIGNUP_FIELDS = [
        Detail\Entity::BUSINESS_TYPE,
        Detail\Entity::TRANSACTION_VOLUME,
        Detail\Entity::ROLE,
        Detail\Entity::DEPARTMENT,
        Detail\Entity::CONTACT_NAME,
        Detail\Entity::BUSINESS_NAME,
        Detail\Entity::CONTACT_MOBILE,
        Detail\Entity::BUSINESS_WEBSITE,
    ];

    const ACTIVATION_MANDATORY_FIELDS = [
        Entity::CATEGORY,
        Entity::BILLING_LABEL,
    ];

    const INSTANT_ACTIVATION_MANDATORY_FIELDS = [
        Entity::CATEGORY,
        Entity::CATEGORY2,
        Entity::BILLING_LABEL,
    ];
}
