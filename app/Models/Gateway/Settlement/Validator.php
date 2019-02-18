<?php

namespace RZP\Models\Gateway\Settlement;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $settlementRules = [
        Service::FROM => 'sometimes',
        Service::TO => 'sometimes',
    ];
}
