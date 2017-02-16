<?php

namespace RZP\Models\Payout;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::METHOD          => 'required|string',
        Entity::AMOUNT          => 'required|integer|max:1000000',
        Entity::CURRENCY        => 'required|size:3',
        Entity::NOTES           => 'sometimes|notes',
        Entity::CUSTOMER_ID     => 'required|public_id',
        Entity::DESTINATION     => 'required|public_id',
    ];

    protected static $createValidators = [
        Entity::METHOD
    ];

    protected function validateMethod($input)
    {
        Method::validateMethod($input[Entity::METHOD]);
    }
}
