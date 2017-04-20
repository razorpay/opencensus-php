<?php

namespace RZP\Models\Aadhaar;

use RZP\Base;
use RZP\Exception;
use RZP\Error\ErrorCode;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::NUMBER         => 'required|string|size:12',
        Entity::BANK           => 'required|string',
        Entity::FINGERPRINT    => 'required|string',
    ];
}
