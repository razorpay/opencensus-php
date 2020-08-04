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
        Entity::NTC_SCORE           => 'sometimes|nullable|int',
        Entity::ERROR_CODE          => 'sometimes|string',
        Entity::SCORE               => 'sometimes|nullable|int',
        Entity::REPORT              => 'sometimes|nullable|json',
        Entity::UFH_FILE_ID         => 'sometimes|nullable|string',
    ];
}
