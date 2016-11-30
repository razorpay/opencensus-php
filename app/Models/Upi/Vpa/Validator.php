<?php

namespace RZP\Models\Upi\Vpa;

use RZP\Base;
use RZP\Exception;
use RZP\Error\ErrorCode;
use Carbon\Carbon;

class Validator extends Base\Validator
{
    protected static $createRules = array(
        Entity::USERNAME        => 'required|string',
        Entity::FREQUENCY       => 'sometimes|string',
        Entity::BANK_ACCOUNT_ID => 'sometimes|string',
        Entity::CUSTOMER_ID     => 'sometimes|string',
    );

    protected static $editRules = array(
        Entity::BANK_ACCOUNT_ID => 'sometimes|string',
    );

    protected static $createValidators = array(
    );
}
