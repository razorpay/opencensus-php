<?php

namespace RZP\Models\BankingAccount\Detail;

use RZP\Base;
use RZP\Models\BankingAccount;

class Validator extends Base\Validator
{
    const PRE_EDIT = 'pre_edit';

    protected static $editRules = [
        Entity::GATEWAY_KEY        => 'required|string',
        Entity::GATEWAY_VALUE      => 'required|string',
    ];
}
