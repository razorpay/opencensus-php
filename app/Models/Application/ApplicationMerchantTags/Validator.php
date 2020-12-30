<?php

namespace RZP\Models\Application\ApplicationMerchantTags;

use RZP\Base;

class Validator extends Base\Validator
{
    const BEFORE_CREATE_MERCHANT_TAG = 'before_create_merchant_tag';

    const BEFORE_UPDATE_MERCHANT_TAG = 'before_update_merchant_tag';

    protected static $beforeCreateMerchantTagRules = [
        Entity::MERCHANT_ID          => 'required|string|max:14',
        Entity::TAG                  => 'required|string|max:255',
    ];

    protected static $beforeUpdateMerchantTagRules = [
        Entity::MERCHANT_ID          => 'required|string|max:14',
        Entity::TAG                  => 'required|string|max:255',
    ];
}
