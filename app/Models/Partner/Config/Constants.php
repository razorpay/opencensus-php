<?php

namespace RZP\Models\Partner\Config;

use RZP\Error\ErrorCode;
use RZP\Models\Base\UniqueIdEntity;
use RZP\Models\Admin\Permission\Name as Permission;

class Constants
{
    const MERCHANT              = 'merchant';
    const PARTNER_ID            = 'partner_id';
    const APPLICATION           = 'application';
    const APPLICATION_ID        = 'application_id';
    const SUBMERCHANT_ID        = 'submerchant_id';
    const ATTRIBUTE_NAME        = 'attribute_name';
    const PARAMETERS            = 'parameters';
    const VALUE                 = 'value';
    const MAX_PAYMENT_AMOUNT    = 'max_payment_amount';
    const GMV_LIMIT             = 'gmv_limit';
    const BUSINESS_TYPE         = 'business_type';
    const SET_FOR               = 'set_for';
    const NO_DOC_SUBMERCHANTS   = 'no_doc_submerchants';
    const MERCHANT_IDS          = 'merchant_ids';
    const ONBOARDING_SOURCE     =  'onboarding_source';
    const ORIGINAL_SIZE         = 'original';
    const LOGO                  = 'logo';
    const BRAND_NAME            = 'brand_name';
    const BRAND_COLOR           = 'brand_color';
    CONST TEXT_COLOR            = 'text_color';
    const LOGO_URL              = 'logo_url';

    const attributes = [
        self::MAX_PAYMENT_AMOUNT,
        self::GMV_LIMIT
    ];

    const attributesParamsMap = [
        self::MAX_PAYMENT_AMOUNT => [
            self::BUSINESS_TYPE
        ],
        self::GMV_LIMIT => [
            self::SET_FOR
        ]
    ];

    const gmvLimitSetFor = [
        self::NO_DOC_SUBMERCHANTS
    ];

    const requiresWorkflow = [
        self::MAX_PAYMENT_AMOUNT,
        self::GMV_LIMIT
    ];

    const attributePermissionMap = [
        self::MAX_PAYMENT_AMOUNT => Permission::EDIT_MERCHANT_RISK_ATTRIBUTES,
        self::GMV_LIMIT          => Permission::EDIT_MERCHANT_RISK_ATTRIBUTES
    ];

    const PARTNER_CONFIG_PUBLIC = [
        UniqueIdEntity::ID,
        Entity::COMMISSION_MODEL,
        Entity::PARTNER_METADATA
    ];

    const PARTNER_CONFIG_LOGO_ERROR_MAP = [
        ErrorCode::BAD_REQUEST_MERCHANT_LOGO_NOT_IMAGE  => ErrorCode::BAD_REQUEST_PARTNER_LOGO_NOT_IMAGE,
        ErrorCode::BAD_REQUEST_MERCHANT_LOGO_TOO_SMALL  => ErrorCode::BAD_REQUEST_PARTNER_LOGO_TOO_SMALL,
        ErrorCode::BAD_REQUEST_MERCHANT_LOGO_NOT_SQUARE => ErrorCode::BAD_REQUEST_PARTNER_LOGO_NOT_SQUARE,
        ErrorCode::BAD_REQUEST_MERCHANT_LOGO_TOO_BIG    => ErrorCode::BAD_REQUEST_PARTNER_LOGO_TOO_BIG
    ];

    const partnerMetaDataAttributes = [
        self::BRAND_NAME,
        self::BRAND_COLOR,
        self::TEXT_COLOR,
        self::LOGO_URL
    ];

    const PARTNER_METADATA_DEFAULT_VALUES = [
        self::BRAND_COLOR  => '528FF0',
        self::TEXT_COLOR   => 'FFFFFF'
    ];
}
