<?php

namespace RZP\Models\Wallet;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::NAME                    => 'required|string|max:50',
        Entity::BALANCE                 => 'required|integer',
        Entity::MIN_BALANCE             => 'required|integer',
        Entity::MAX_BALANCE             => 'required|integer',
    ];
}
