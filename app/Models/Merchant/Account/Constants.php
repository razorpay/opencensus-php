<?php

namespace RZP\Models\Merchant\Account;

use RZP\Models\Merchant\Detail\Status;

class Constants
{
    // Response keys
    const ENTITY             = 'entity';
    const ID                 = 'id';
    const BUSINESS_ENTITY    = 'business_entity';
    const MANAGED            = 'managed';
    const EMAIL              = 'email';
    const PHONE              = 'phone';
    const LEGAL_ENTITY_ID    = 'legal_entity_id';
    const NOTES              = 'notes';
    const ACCOUNT_ACCESS     = 'account_access';
    const REVIEW_STATUS      = 'review_status';
    const CURRENT_STATE      = 'current_state';
    const REQUIREMENTS       = 'requirements';
    const BUSINESSES         = 'businesses';
    const FIELDS             = 'fields';
    const FIELD_NAME         = 'field_name';
    const DOCUMENTS          = 'documents';
    const STATUS             = 'status';
    const PAYMENT_ENABLED    = 'payment_enabled';
    const SETTLEMENT_ENABLED = 'settlement_enabled';
    const PROFILE            = 'profile';
    const ADDRESSES          = 'addresses';
    const TYPE               = 'type';
    const RELATIONSHIP       = 'relationship';
    const LINE1              = 'line1';
    const LINE2              = 'line2';
    const CITY               = 'city';
    const DISTRICT_NAME      = 'district_name';
    const STATE              = 'state';
    const PIN                = 'pin';
    const COUNTRY            = 'country';
    const NAME               = 'name';
    const DESCRIPTION        = 'description';
    const BUSINESS_MODEL     = 'business_model';
    const MCC                = 'mcc';
    const BRAND              = 'brand';
    const ICON               = 'icon';
    const LOGO               = 'logo';
    const COLOR              = 'color';
    const DASHBOARD_DISPLAY  = 'dashboard_display';
    const WEBSITE            = 'website';
    const APPS               = 'apps';
    const LINKS              = 'links';
    const ANDROID            = 'android';
    const IOS                = 'ios';
    const SUPPORT            = 'support';
    const POLICY             = 'policy';
    const URL                = 'url';
    const CHARGEBACK         = 'chargeback';
    const REFUND             = 'refund';
    const DISPUTE            = 'dispute';
    const BILLING_LABEL      = 'billing_label';
    const PAYMENT            = 'payment';
    const REASON             = 'reason';
    const DISABLED_REASON    = 'disabled_reason';
    const FLASH_CHECKOUT     = 'flash_checkout';
    const EMI                = 'emi';
    const INTERNATIONAL      = 'international';
    const SETTLEMENT         = 'settlement';
    const SETTINGS           = 'settings';
    const BALANCE_RESERVED   = 'balance_reserved';
    const FUND_ACCOUNT_ID    = 'fund_account_id';
    const SCHEDULES          = 'schedules';
    const INTERVAL           = 'interval';
    const FUND_ACCOUNTS      = 'fund_accounts';
    const CONTACT_ID         = 'contact_id';
    const CONTACT_NAME       = 'contact_name';
    const BANK_ACCOUNT       = 'bank_account';
    const ACCOUNT_NUMBER     = 'account_number';
    const IFSC               = 'ifsc';
    const TNC                = 'tnc';
    const CREATED_AT         = 'created_at';
    const OWNER_INFO         = 'owner_info';
    const CONTACT_INFO       = 'contact_info';
    const LIVE               = 'live';
    const HOLD_FUNDS         = 'hold_funds';
    const ACTIVATED_AT       = 'activated_at';

    // feature flags
    const NO_DOC_ONBOARDING  = 'no_doc_onboarding';

    // tags
    const NO_DOC_LIMIT_BREACHED = 'no_doc_limit_breached';
    const NO_DOC_PARTIALLY_ACTIVATED = 'no_doc_partially_activated';

    // tags
    const INSTANT_ACTIVATION_SUBM = 'instant_activation_subm';

    // external ids
    const EXTERNAL_ID       = 'external_id';
    const LEGAL_EXTERNAL_ID = 'legal_external_id';

    // Values
    const ACCOUNT            = 'account';
    const OPERATION          = 'operation';
    const REGISTERED         = 'registered';

    const IDENTIFICATION        = 'identification';
    const DOCUMENT              = 'document';
    const IDENTIFICATION_NUMBER = 'identification_number';

    // defaults
    const DEFAULT_ACCOUNT_COUNT = 20;

    // reasons
    const REQUIRED_DOCUMENT_MISSING = 'required_document_missing';
    const REQUIRED_FIELD_MISSING    = 'required_field_missing';

    // business model values
    const B2B  = 'B2B';
    const B2C  = 'B2C';
    const B2BC = 'B2B+B2C';

    const REFERENCE_ID        = 'reference_id';
    const LEGAL_BUSINESS_NAME = 'legal_business_name';
    const BUSINESS_TYPE       = 'business_type';
    const LEGAL_INFO          = 'legal_info';
    const TOS_ACCEPTANCE      = 'tos_acceptance';
    const CATEGORY            = 'category';
    const SUBCATEGORY         = 'subcategory';
    const POSTAL_CODE         = 'postal_code';
    const STREET1             = 'street1';
    const STREET2             = 'street2';
    const PAN                 = 'pan';
    const GST                 = 'gst';
    const CIN                 = 'cin';
    const USER_AGENT          = 'user_agent';
    const DATE                = 'date';
    const IP                  = 'ip';
    const POLICY_URL          = 'policy_url';
    const WEBSITES            = 'websites';
    const STANDARD            = 'standard';
    const MERCHANT_IDS        = 'merchant_ids';

    const CUSTOMER_FACING_BUSINESS_NAME = 'customer_facing_business_name';


    // bank account statuses
    const PENDING_VERIFICATION = 'pending_verification';
    const ACTIVE               = 'active';

    const CREATED              = 'created';
    const SUSPENDED            = 'suspended';
    const SUSPENDED_AT         = 'suspended_at';

    const IS_IGNORE_TOS_ACCEPTANCE         = 'isIgnoreTosAcceptance';

    public static $validBusinessModels = [
        self::B2B,
        self::B2C,
        self::B2BC,
    ];

    //Linked account fetch response, activation status enum values
    const ACTIVATED             = "activated";
    const VERIFICATION_PENDING  = "verification_pending";
    const VERIFICATION_FAILED   = "verification_failed";

    const NOT_ACTIVATED         = 'Not Activated';

    const LA_ACTIVATION_STATUS_MAPPING = [
        self::ACTIVATED => 'Activated',
        self::VERIFICATION_PENDING => 'Verification Pending',
        self::VERIFICATION_FAILED => 'Verification Failed'
    ];

    public static $validAddressTypes = [
        self::REGISTERED,
        self::OPERATION,
    ];

    const ACTIVATION_STATUS_ACCOUNT_STATUS_MAPPING = [
        null                            => self::CREATED,
        Status::UNDER_REVIEW            => Status::UNDER_REVIEW,
        Status::NEEDS_CLARIFICATION     => Status::NEEDS_CLARIFICATION,
        Status::ACTIVATED               => Status::ACTIVATED,
        Status::REJECTED                => Status::REJECTED,
        Status::ACTIVATED_KYC_PENDING   => Status::ACTIVATED_KYC_PENDING,
        Status::ACTIVATED_MCC_PENDING   => Status::ACTIVATED
    ];
}
