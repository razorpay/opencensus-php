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
    const BUSINESS_TYPE            = 'business_type';

    const attributes = [
        self::MAX_PAYMENT_AMOUNT
    ];

    const attributesParamsMap = [
        self::MAX_PAYMENT_AMOUNT => [
            self::BUSINESS_TYPE
        ],
    ];

    const requiresWorkflow = [
        self::MAX_PAYMENT_AMOUNT
    ];

    const attributePermissionMap = [
        self::MAX_PAYMENT_AMOUNT => Permission::EDIT_MERCHANT_RISK_ATTRIBUTES
    ];
}
