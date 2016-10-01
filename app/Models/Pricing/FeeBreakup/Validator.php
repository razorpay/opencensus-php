<?php

namespace RZP\Models\Pricing\FeeBreakup;

use RZP\Models\Base;
use RZP\Exception;
use RZP\Error\ErrorCode;

class Validator extends Base\Validator
{
    protected static $createRules = array(
        Entity::NAME            => 'required|string',
        Entity::PERCENTAGE      => 'required|integer',
        Entity::AMOUNT          => 'required|integer',
        Entity::TYPE            => 'required|string'
    );
}
