<?php

namespace RZP\Models\BankingAccount\Bank\Rbl;

use RZP\Base;
use RZP\Models\BankingAccount\Entity;
use RZP\Exception\BadRequestValidationFailureException;

class Validator extends Base\Validator
{
    protected static $availabilityRules = [
        Entity::CHANNEL => 'required',
        Entity::PINCODE => 'required',
    ];
}
