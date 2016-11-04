<?php

namespace RZP\Models\Pricing\FeeBreakup;

use RZP\Base;
use RZP\Exception;
use RZP\Error\ErrorCode;

class Validator extends Base\Validator
{
    protected static $createRules = array(
        Entity::NAME                    => 'required|string',
        Entity::PERCENTAGE              => 'sometimes|integer',
        Entity::PRICING_RULE_ID         => 'sometimes|string',
        Entity::AMOUNT                  => 'required|integer',
    );
}
