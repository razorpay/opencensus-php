<?php

namespace RZP\Models\AddOn;

use RZP\Base;
use RZP\Exception;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::NAME        => 'required|string|max:50',
        // TODO: Add more validations like if other attributes are not sent, item_id is mandatory
        // This can be done in core also, while creating the add_on.
        Entity::ITEM_ID     => 'sometimes|public_id',
        Entity::AMOUNT      => 'required|integer|min:100|max:50000000',
        Entity::CURRENCY    => 'required|size:3|in:INR',
    ];
}
