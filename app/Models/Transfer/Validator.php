<?php

namespace RZP\Models\Transfer;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::FROM                    => 'required|string',
        Entity::FROM_ID                 => 'required|alpha_num|size:14',
        Entity::TO                      => 'required|string',
        Entity::TO_ID                   => 'required|alpha_num|size:14',
        Entity::AMOUNT                  => 'required|integer',
        Entity::TRANSACTION_ID          => 'required|alpha_num|size:14'
    ];
}
