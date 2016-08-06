<?php

namespace RZP\Models\Terminal\Absence;

use RZP\Models\Base;

class Validator extends Base\Validator
{

    protected static $createRules = array (
        Entity::GATEWAY         => 'required',
        Entity::DOWNTIME_FROM   => 'required|date|date_format:Y-m-d H:i:s',
        Entity::DOWNTIME_TO     => 'required|date|date_format:Y-m-d H:i:s',
        Entity::REASON          => 'sometimes|string'
    );
}
