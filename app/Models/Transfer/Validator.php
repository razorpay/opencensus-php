<?php

namespace RZP\Models\Transfer;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::TO_ID          => 'required|alpha_num|size:14',
        Entity::TO_TYPE        => 'required|string',
        Entity::SOURCE_ID      => 'required|alpha_num|size:14',
        Entity::SOURCE_TYPE    => 'required|string',
        Entity::AMOUNT         => 'required|integer',
    ];
}
