<?php

namespace RZP\Models\DeviceDetail;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::MERCHANT_ID            => 'required|string|size:14',
        Entity::USER_ID                => 'required|string|size:14',
        Entity::APPSFLYER_ID           => 'required|string',
    ];

    protected static $editRules = [
        Entity::MERCHANT_ID            => 'required|string|size:14',
        Entity::USER_ID                => 'required|string|size:14',
        Entity::APPSFLYER_ID           => 'required|string',
    ];

    protected static $appsFlyerIdInputRules = [
        Entity::APPSFLYER_ID           => 'required|string',
    ];
}
