<?php

namespace RZP\Models\Coupon;

use Carbon\Carbon;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::ENTITY_ID   => 'required|string|max:14',
        Entity::ENTITY_TYPE => 'required|string|max:20',
        Entity::COUPON_CODE => 'required|string|max:10',
        Entity::START_DATE  => 'sometimes|integer',
        Entity::END_DATE    => 'sometimes|integer',
        Entity::USAGE       => 'sometimes|integer',
    ];
}
