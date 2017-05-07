<?php

namespace RZP\Models\Plan\Subscription\Addon;

use RZP\Base;
use RZP\Exception;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::ITEM_ID     => 'required_without:item|public_id',
        Entity::ITEM        => 'required_without:item_id|array',
    ];
}
