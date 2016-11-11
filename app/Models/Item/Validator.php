<?php

namespace RZP\Models\Item;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::NAME                => 'required|string',
        Entity::DESCRIPTION         => 'sometimes|string|max:2048',
        Entity::AMOUNT              => 'required|integer',
        Entity::CURRENCY            => 'sometimes|size:3|in:INR',
    ];
}
