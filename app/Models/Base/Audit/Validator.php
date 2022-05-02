<?php

namespace RZP\Models\Base\Audit;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::ID   => 'required|string|size:14',
        Entity::META => 'required|json'
    ];
}
