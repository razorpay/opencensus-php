<?php

namespace RZP\Models\Terminal\Action;

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\Base;
use RZP\Models\Terminal;

class Validator extends Base\Validator
{

    protected static $createRules = array(
        Entity::TERMINAL_ID => 'required|alpha_num|size:14',
        Entity::ACTION => 'required|string|in:ACTIVATED,SUSPENDED,PRIORITY_CHANGE'
    );
}
