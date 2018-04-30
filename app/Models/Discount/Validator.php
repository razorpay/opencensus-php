<?php

namespace RZP\Models\Discount;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::AMOUNT     => 'required|integer',
    ];
}
