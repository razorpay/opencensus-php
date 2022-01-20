<?php


namespace RZP\Services\Segment;

use RZP\Models\Merchant;

class Config
{
    const MANDATORY_USER_PROPERTY_MAP = [
        EventCode::SIGNUP_SUCCESS       => [
            Merchant\Entity::MERCHANT_ID,
            Merchant\Entity::ORG_ID,
        ],

        EventCode::L1_SUBMISSION        => [
            Merchant\Entity::MERCHANT_ID,
            Merchant\Entity::ORG_ID,
            "mcc",

            Merchant\Detail\Entity::BUSINESS_TYPE,
            Merchant\Detail\Entity::BUSINESS_CATEGORY,
            Merchant\Detail\Entity::BUSINESS_SUBCATEGORY,
            Merchant\Detail\Entity::BUSINESS_DBA,
            Merchant\Detail\Entity::ACTIVATION_STATUS,
            Merchant\Detail\Entity::ACTIVATION_FORM_MILESTONE,
            Constants::REGULAR_MERCHANT
        ],

        EventCode::PAYMENTS_ENABLED     => [
            Merchant\Entity::MERCHANT_ID,
            Merchant\Entity::ORG_ID,
            "mcc",

            Merchant\Detail\Entity::BUSINESS_TYPE,
            Merchant\Detail\Entity::BUSINESS_CATEGORY,
            Merchant\Detail\Entity::BUSINESS_SUBCATEGORY,
            Merchant\Detail\Entity::BUSINESS_DBA,
            Merchant\Detail\Entity::ACTIVATION_STATUS,
            Merchant\Detail\Entity::ACTIVATION_FORM_MILESTONE,
            Constants::REGULAR_MERCHANT,

            Merchant\Entity::ACTIVATED_AT,
        ],

        EventCode::L2_SUBMISSION        => [
            Merchant\Entity::MERCHANT_ID,
            Merchant\Entity::ORG_ID,
            "mcc",

            Merchant\Detail\Entity::BUSINESS_TYPE,
            Merchant\Detail\Entity::BUSINESS_CATEGORY,
            Merchant\Detail\Entity::BUSINESS_SUBCATEGORY,
            Merchant\Detail\Entity::BUSINESS_DBA,
            Merchant\Detail\Entity::ACTIVATION_STATUS,
            Merchant\Detail\Entity::ACTIVATION_FORM_MILESTONE,
            Constants::REGULAR_MERCHANT,
        ],

        EventCode::DEDUPE               => [
            Merchant\Entity::MERCHANT_ID,
            Merchant\Entity::ORG_ID,
            "mcc",

            Merchant\Detail\Entity::BUSINESS_TYPE,
            Merchant\Detail\Entity::BUSINESS_CATEGORY,
            Merchant\Detail\Entity::BUSINESS_SUBCATEGORY,
            Merchant\Detail\Entity::BUSINESS_DBA,
            Merchant\Detail\Entity::ACTIVATION_STATUS,
            Merchant\Detail\Entity::ACTIVATION_FORM_MILESTONE,
            Constants::REGULAR_MERCHANT,

            'dedupe_status',
            'dedupe_timestamp'
        ],

        EventCode::MTU_TRANSACTED       => [
            Merchant\Entity::MERCHANT_ID,
            Merchant\Entity::ORG_ID,
            "mcc",

            Merchant\Detail\Entity::BUSINESS_TYPE,
            Merchant\Detail\Entity::BUSINESS_CATEGORY,
            Merchant\Detail\Entity::BUSINESS_SUBCATEGORY,
            Merchant\Detail\Entity::BUSINESS_DBA,
            Merchant\Detail\Entity::ACTIVATION_STATUS,
            Merchant\Detail\Entity::ACTIVATION_FORM_MILESTONE,
            Constants::REGULAR_MERCHANT,

            'mtu',
            'first_transaction_timestamp',
            'previous_activation_status',
            'is_m2m_referral',
        ],

        EventCode::IDENTIFY_WEB_ATTRIBUTION     => [
            'web_acquisition_campaign',
            'web_acquisition_medium',
            'web_acquisition_source',
            'web_device'
        ],

        EventCode::IDENTIFY_APP_ATTRIBUTION     => [
            'app_appsflyer_id',
            'app_campaign_type',
            'app_conversion_type',
            'app_media_source',
            'app_event_source',
        ]
    ];
}
