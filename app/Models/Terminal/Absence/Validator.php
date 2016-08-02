<?php

namespace RZP\Models\Terminal\Absence;

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\Base;
use RZP\Models\Terminal;

class Validator extends Base\Validator
{

    protected static $createRules = array(
        Entity::GATEWAY => 'required',
        Entity::DOWNTIME_FROM => 'required|date|date_format:Y-m-d H:i:s',
        Entity::DOWNTIME_TO => 'required|date|date_format:Y-m-d H:i:s'
    );
}
