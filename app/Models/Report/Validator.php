<?php

namespace RZP\Models\Report;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::ENTITY          => 'required|string',
        Entity::START_TIME      => 'required|integer',
        Entity::END_TIME        => 'required|integer',
    ];
}
