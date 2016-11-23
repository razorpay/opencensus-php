<?php

namespace RZP\Models\LineItem;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::QUANTITY            => 'sometimes|integer|min:1',
        Entity::ITEM_ID             => 'sometimes|string|max:19',
    ];
}
