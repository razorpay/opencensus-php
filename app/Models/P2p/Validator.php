<?php

namespace RZP\Models\P2p;

use RZP\Base;
use RZP\Exception;
use RZP\Error\ErrorCode;

class Validator extends Base\Validator
{
    protected static $createRules = array(
        Entity::SOURCE_ID           => 'required',
        Entity::SINK_ID             => 'required',
        Entity::MERCHANT_ID         => 'required',
        Entity::AMOUNT              => 'required',
        Entity::DESCRIPTION         => 'sometimes',
        Entity::TYPE                => 'required|in:collect,send',
        Entity::NOTES               => 'sometimes',
        Entity::CURRENCY            => 'required|in:INR',
    );

    protected static $createValidators = array(
    );
}
