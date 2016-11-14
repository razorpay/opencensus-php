<?php

namespace RZP\Models\LineItem;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::NAME                => 'required|string|max:128',
        Entity::DESCRIPTION         => 'sometimes|string|max:2048',
        // Entity::LISTING_ID          => 'sometimes|string|max:512',
        Entity::AMOUNT              => 'required|integer',
        // Entity::CURRENCY            => 'sometimes|size:3|in:INR',
        Entity::QUANTITY            => 'sometimes|integer|min:1',
    ];
}
