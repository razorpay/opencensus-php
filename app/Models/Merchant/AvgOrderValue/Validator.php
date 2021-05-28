<?php

namespace RZP\Models\Merchant\AvgOrderValue;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::MERCHANT_ID         => 'sometimes|string|size:14',
        Entity::MIN_AOV             => 'required|numeric',
        Entity::MAX_AOV             => 'required|numeric'
    ];

    protected static $editRules = [
        Entity::MIN_AOV             => 'required|numeric',
        Entity::MAX_AOV             => 'required|numeric'
    ];
}
