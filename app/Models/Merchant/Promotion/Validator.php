<?php

namespace RZP\Models\Merchant\Promotion;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::START_TIME            => 'required|epoch',
        Entity::REMAINING_ITERATIONS  => 'required|integer|min:1',
    ];
}
