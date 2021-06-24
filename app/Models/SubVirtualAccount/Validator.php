<?php

namespace RZP\Models\SubVirtualAccount;

use RZP\Base;

/**
 * Class Validator
 *
 * @package RZP\Models\SubVirtualAccount
 */
class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::NAME                   => 'required|regex:/^[a-zA-Z0-9][\w\-&\'’,.:()\s\/]+$/|between:4,120|string',
        Entity::MASTER_ACCOUNT_NUMBER  => 'required|string|between:5,35',
        Entity::SUB_ACCOUNT_NUMBER     => 'required|string|between:5,35',
    ];
}
