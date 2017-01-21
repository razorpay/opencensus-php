<?php

namespace RZP\Models\Schedule\Run;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::NEXT_RUN_AT => 'required|integer'
    ];
}
