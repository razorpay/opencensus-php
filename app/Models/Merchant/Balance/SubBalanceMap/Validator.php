<?php

namespace RZP\Models\Merchant\Balance\SubBalanceMap;

use RZP\Base;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant\Balance\Entity as BalanceEntity;

class Validator extends Base\Validator
{
    const CREATE_BALANCE_RULES = 'create_sub_balance';

    protected static $createRules = [
        Entity::MERCHANT_ID       => 'required|size:14',
        Entity::PARENT_BALANCE_ID => 'required|size:14',
        Entity::CHILD_BALANCE_ID  => 'required|size:14',
    ];

    protected static $createSubBalanceRules = [
        Entity::PARENT_BALANCE_ID => 'required|size:14',
    ];
}
