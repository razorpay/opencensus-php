<?php

namespace RZP\Models\PayoutMeta;

use RZP\Base;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::PAYOUT_ID      => 'required|string',
        Entity::PARTNER_ID     => 'required|string',
        Entity::APPLICATION_ID => 'required|string',
    ];
}

