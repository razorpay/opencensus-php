<?php

namespace RZP\Models\Coupon;

use Carbon\Carbon;

use RZP\Base;
use RZP\Exception;
use RZP\Error\ErrorCode;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::ENTITY_ID   => 'required|string|max:24',
        Entity::ENTITY_TYPE => 'required|string|max:255',
        Entity::COUPON_CODE => 'required|string|max:20',
        Entity::START_DATE  => 'sometimes|integer',
        Entity::END_DATE    => 'sometimes|integer',
    ];
}
