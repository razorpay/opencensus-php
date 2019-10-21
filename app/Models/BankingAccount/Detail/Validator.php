<?php

namespace RZP\Models\BankingAccount\Detail;

use RZP\Base;

class Validator extends Base\Validator
{
    const PRE_EDIT = 'pre_edit';

    protected static $preEditRules = [
        Entity::DETAILS => 'required|array',
    ];

    protected static $editRules = [
        Entity::GATEWAY_KEY        => 'required|string',
        Entity::GATEWAY_VALUE      => 'required|string',
    ];
}
