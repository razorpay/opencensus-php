<?php

namespace RZP\Models\Merchant\Account;

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
    const BANK_ACCOUNT       = 'bank_account';
    const ACCOUNT_NUMBER     = 'account_number';
    const IFSC               = 'ifsc';
    const TNC                = 'tnc';
    const CREATED_AT         = 'created_at';
    const OWNER_INFO         = 'owner_info';
    const CONTACT_INFO       = 'contact_info';

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

    // bank account statuses
    const PENDING_VERIFICATION = 'pending_verification';
    const ACTIVE               = 'active';

    public static $validBusinessModels = [
        self::B2B,
        self::B2C,
        self::B2BC,
    ];
}
