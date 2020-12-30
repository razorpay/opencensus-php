<?php

namespace RZP\Models\Application\ApplicationMerchantMaps;

use RZP\Base;

class Validator extends Base\Validator
{
    const BEFORE_CREATE_MERCHANT_MAPPING = 'before_create_merchant_mapping';

    const BEFORE_UPDATE_MERCHANT_MAPPING = 'before_update_merchant_mapping';

    protected static $beforeCreateMerchantMappingRules = [
        Entity::MERCHANT_ID          => 'required|string|max:14',
        Entity::APP_ID               => 'required|string|max:14',
    ];

    protected static $beforeUpdateMerchantMappingRules = [
        Entity::MERCHANT_ID          => 'required|string|max:14',
        Entity::APP_ID               => 'required|string|max:14',
        Entity::ENABLED              => 'required|boolean',
    ];
}
