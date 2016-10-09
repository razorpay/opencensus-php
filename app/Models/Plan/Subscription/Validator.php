<?php

namespace RZP\Models\Plan\Subscription;

use RZP\Models\Base;
use RZP\Exception;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::QUANTITY    => 'required|integer|max:500',
        Entity::NOTES       => 'sometimes|notes'
    ];
}