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
        Entity::SCORE               => 'required|int',
        Entity::REPORT              => 'required|json',
        Entity::UFH_FILE_ID         => 'required|string',
    ];
}
