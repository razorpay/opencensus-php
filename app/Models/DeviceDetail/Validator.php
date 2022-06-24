<?php

namespace RZP\Models\DeviceDetail;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::MERCHANT_ID            => 'required|string|size:14',
        Entity::USER_ID                => 'required|string|size:14',
        Entity::APPSFLYER_ID           => 'string|required_if:signup_source,ios,android',
        Entity::SIGNUP_SOURCE          => 'sometimes|string|nullable',
        Entity::SIGNUP_CAMPAIGN        => 'sometimes|string|nullable',
        Entity::METADATA                                             => 'sometimes|array',
        Entity::METADATA . '.' . Constants::CLIENT_IP                => 'sometimes|ip|nullable',
        Entity::METADATA . '.' . Constants::G_CLIENT_ID              => 'sometimes|string|nullable',
        Entity::METADATA . '.' . Constants::G_CLICK_ID               => 'sometimes|string|nullable',
    ];

    protected static $editRules = [
        Entity::MERCHANT_ID            => 'required|string|size:14',
        Entity::USER_ID                => 'required|string|size:14',
        Entity::APPSFLYER_ID           => 'string|required_if:signup_source,ios,android',
        Entity::SIGNUP_SOURCE          => 'sometimes|string|nullable',
        Entity::SIGNUP_CAMPAIGN        => 'sometimes|string|nullable',
        Entity::METADATA                                             => 'sometimes|array',
        Entity::METADATA . '.' . Constants::CLIENT_IP                => 'sometimes|ip|nullable',
        Entity::METADATA . '.' . Constants::G_CLIENT_ID              => 'sometimes|string|nullable',
        Entity::METADATA . '.' . Constants::G_CLICK_ID               => 'sometimes|string|nullable',
    ];

    protected static $appsFlyerIdInputRules = [
        Entity::APPSFLYER_ID           => 'string|required_if:signup_source,ios,android',
        Entity::SIGNUP_SOURCE          => 'sometimes|string|nullable',
    ];
}
