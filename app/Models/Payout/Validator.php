<?php

namespace RZP\Models\Payout;

use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\Base;
use RZP\Models\Payout;

class Validator extends Base\Validator
{
    protected static $createRules = array(
        Entity::METHOD          => 'required|string',
        Entity::AMOUNT          => 'required|integer|max:1000000',
        Entity::CURRENCY        => 'required|size:3',
        Entity::NOTES           => 'sometimes|notes'
    );

    protected static $createValidators = array(
        Entity::METHOD
    );

    protected function validateMethod($input)
    {
        Payout\Method::validateMethod($input[Payout\Entity::METHOD]);
    }
}