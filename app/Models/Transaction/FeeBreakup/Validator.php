<?php

namespace RZP\Models\Transaction\FeeBreakup;

use RZP\Base;
use RZP\Exception;
use RZP\Error\ErrorCode;

class Validator extends Base\Validator
{
    protected static $createRules = array(
        Entity::NAME                    => 'required|string',
        Entity::PERCENTAGE              => 'sometimes|nullable|integer',
        Entity::AMOUNT                  => 'required|integer',
    );
}
