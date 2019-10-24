<?php

namespace RZP\Models\Merchant\LegalEntity;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::BUSINESS_TYPE        => 'sometimes|integer|digits_between:1,10',
        Entity::MCC                  => 'sometimes|integer',
        Entity::BUSINESS_CATEGORY    => 'sometimes|string|max:255',
        Entity::BUSINESS_SUBCATEGORY => 'sometimes|string|max:255',
    ];

    protected static $editRules = [
        Entity::BUSINESS_TYPE        => 'sometimes|integer|digits_between:1,10',
        Entity::MCC                  => 'sometimes|integer',
        Entity::BUSINESS_CATEGORY    => 'sometimes|string|max:255',
        Entity::BUSINESS_SUBCATEGORY => 'sometimes|string|max:255',
    ];
}
