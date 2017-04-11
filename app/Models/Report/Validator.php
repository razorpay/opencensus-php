<?php

namespace RZP\Models\Report;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::DAY             => 'required|integer|max:31',
        Entity::MONTH           => 'required|integer|max:12',
        Entity::YEAR            => 'required|integer',
        Entity::TYPE            => 'required|string',
        Entity::START_TIME      => 'required|integer',
        Entity::END_TIME        => 'required|integer',
        Entity::GENERATED_BY    => 'required|string',
    ];
}
