<?php

namespace RZP\Models\PayoutsDetails;

use RZP\Base;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::PAYOUT_ID                   => 'required|string|size:14',
        Entity::QUEUE_IF_LOW_BALANCE_FLAG   => 'required|bool',

    ];
}

