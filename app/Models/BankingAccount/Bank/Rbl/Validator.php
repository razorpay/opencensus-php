<?php

namespace RZP\Models\BankingAccount\Bank\Rbl;

use RZP\Base;
use RZP\Models\BankingAccount\Entity;
use RZP\Exception\BadRequestValidationFailureException;

class Validator extends Base\Validator
{
    protected static $availabilityRules = [
        Entity::BANK              => 'required',
        Entity::PINCODE           => 'filled|custom',
    ];

    protected function validatePincode(string $attribute, string $pincode)
    {
        if ((new Core)->checkPincodeServiceable($pincode) === false)
        {
            throw new BadRequestValidationFailureException("Pincode not serviceable",
                                                            Entity::PINCODE,
                                                            [
                                                                Entity::PINCODE => $pincode,
                                                            ]);
        }
    }
}
