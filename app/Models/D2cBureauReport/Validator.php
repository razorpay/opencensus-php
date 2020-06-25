<?php

namespace RZP\Models\D2cBureauReport;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $editRules = [
        Entity::INTERESTED          => 'required|bool',
    ];

    protected static $createRules = [
        Entity::PROVIDER            => 'required|string',
        Entity::ERROR_CODE          => 'sometimes|string',
        Entity::SCORE               => 'sometimes|int',
        Entity::REPORT              => 'sometimes|json',
        Entity::UFH_FILE_ID         => 'sometimes|string',
    ];
}
