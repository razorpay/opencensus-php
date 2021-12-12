<?php


namespace RZP\Models\DeviceDetail\Attribution;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::MERCHANT_ID            => 'required|string|size:14',
        Entity::USER_ID                => 'required|string|size:14',
        Entity::APPSFLYER_ID           => 'required|string',
        Entity::INSTALL_TIME               =>'sometimes|string|nullable',
        Entity::EVENT_TYPE                 =>'sometimes|string|nullable',
        Entity::EVENT_TIME                 =>'sometimes|string|nullable',
        Entity::DEVICE_TYPE                =>'sometimes|string|nullable',
        Entity::DEVICE_CATEGORY            =>'sometimes|string|nullable',
        Entity::PLATFORM                   =>'sometimes|string|nullable',
        Entity::OS_VERSION                 =>'sometimes|string|nullable',
        Entity::APP_VERSION                =>'sometimes|string|nullable',

        Entity::CONTRIBUTOR_1_ATTRIBUTES   =>'sometimes|array',
        Entity::CONTRIBUTOR_2_ATTRIBUTES   =>'sometimes|array',
        Entity::CONTRIBUTOR_3_ATTRIBUTES   =>'sometimes|array',
        Entity::CAMPAIGN_ATTRIBUTES        =>'sometimes|array',
    ];

    protected static $editRules = [
        Entity::MERCHANT_ID            => 'required|string|size:14',
        Entity::USER_ID                => 'required|string|size:14',
        Entity::APPSFLYER_ID           => 'required|string',
        Entity::INSTALL_TIME               =>'sometimes|string|nullable',
        Entity::EVENT_TYPE                 =>'sometimes|string|nullable',
        Entity::EVENT_TIME                 =>'sometimes|string|nullable',
        Entity::DEVICE_TYPE                =>'sometimes|string|nullable',
        Entity::DEVICE_CATEGORY            =>'sometimes|string|nullable',
        Entity::PLATFORM                   =>'sometimes|string|nullable',
        Entity::OS_VERSION                 =>'sometimes|string|nullable',
        Entity::APP_VERSION                =>'sometimes|string|nullable',
        Entity::CONTRIBUTOR_1_ATTRIBUTES   =>'sometimes|array',
        Entity::CONTRIBUTOR_2_ATTRIBUTES   =>'sometimes|array',
        Entity::CONTRIBUTOR_3_ATTRIBUTES   =>'sometimes|array',
        Entity::CAMPAIGN_ATTRIBUTES        =>'sometimes|array',
    ];
}
