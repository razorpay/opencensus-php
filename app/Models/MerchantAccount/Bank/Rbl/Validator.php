<?php

namespace RZP\Models\MerchantAccount\Bank\Rbl;

use RZP\Base;
use RZP\Models\MerchantAccount\Entity;
use RZP\Exception\BadRequestValidationFailureException;

class Validator extends Base\Validator
{
    protected static $availabilityRules = [
        Entity::BANK              => 'required',
        Entity::MERCHANT_ID       => 'required',
        Entity::PINCODE           => 'required|custom',
    ];

    protected function validatePincode(string $attribute, string $pincode)
    {
        if (empty($pincode) === true)
        {
            throw new BadRequestValidationFailureException('Pincode not present');
        }

        if ((new Core)->checkPincodeServiceable($pincode) === false)
        {
            throw new BadRequestValidationFailureException("Pincode not serviceable");
        }
    }
}
