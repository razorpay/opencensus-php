<?php

namespace RZP\Models\Settlement\Destination;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::DESTINATION_ID   => 'required|string|size:14',
        Entity::DESTINATION_TYPE => 'required|string|max:255',
        Entity::SETTLEMENT_ID    => 'required|string|size:14',
    ];
}
