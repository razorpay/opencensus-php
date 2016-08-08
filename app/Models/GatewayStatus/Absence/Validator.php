<?php

namespace RZP\Models\Terminal\Absence;

use RZP\Models\Base;

class Validator extends Base\Validator
{
    protected static $createRules = array (
        Entity::GATEWAY         => 'required',
        Entity::DOWNTIME_FROM   => 'required|integer',
        Entity::DOWNTIME_TO     => 'required|integer',
        Entity::REASON          => 'sometimes|string'
    );
}
