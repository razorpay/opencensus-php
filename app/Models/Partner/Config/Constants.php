<?php

namespace RZP\Models\Partner\Config;

use RZP\Models\Admin\Permission\Name as Permission;

class Constants
{
    const MERCHANT       = 'merchant';
    const PARTNER_ID     = 'partner_id';
    const APPLICATION    = 'application';
    const APPLICATION_ID = 'application_id';
    const SUBMERCHANT_ID = 'submerchant_id';
    const ATTRIBUTE_NAME = 'attribute_name';
    const PARAMETERS     = 'parameters';
    const VALUE          = 'value';
    const MAX_PAYMENT_AMOUNT       = 'max_payment_amount';
    const GMV_LIMIT                = 'gmv_limit';
    const BUSINESS_TYPE            = 'business_type';
    const SET_FOR                  = 'set_for';
    const NO_DOC_SUBMERCHANTS      = 'no_doc_submerchants';

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
}
