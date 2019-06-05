<?php

namespace RZP\Models\BankingAccount;

use RZP\Base;
use RZP\Exception\BadRequestValidationFailureException;

class Validator extends Base\Validator
{
    protected static $preCreateRules = [
        Entity::CHANNEL => 'required|string|in:rbl',
    ];

    protected static $rblAvailabilityRules = [
        Entity::CHANNEL => 'required|string|in:rbl',
        Entity::PINCODE => 'required_if:channel,rbl',
    ];

    protected static $createRules = [
        Entity::CHANNEL => 'required|string|in:rbl',
        Entity::PINCODE => 'required_if:channel,rbl',
    ];

    protected static $serviceablePincodeRules = [
        Entity::CHANNEL         => 'required|string|in:rbl',
        Entity::ACTION          => 'required|string|in:add,delete',
        Entity::PINCODES        => 'required|array|filled',
    ];

    protected static $serviceablePincodeValidators = [
        Entity::PINCODE,
    ];

    public function validatePincode(array $input)
    {
        foreach ($input[Entity::PINCODES] as $pincode)
        {
            if (preg_match(Entity::PINCODE_REGEX, $pincode) === 0)
            {
                throw new BadRequestValidationFailureException(
                    'Pincode is not valid',
                    Entity::PINCODE,
                    [
                        Entity::PINCODE => $pincode,
                    ]
                );
            }
        }
    }
}
