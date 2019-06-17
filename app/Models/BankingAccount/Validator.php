<?php

namespace RZP\Models\BankingAccount;

use RZP\Base;
use RZP\Models\Pincode;
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
        Entity::PINCODES,
    ];

    public function validatePincodes(array $input)
    {
        foreach ($input[Entity::PINCODES] as $pincode)
        {
            $pincodeValidator = new Pincode\Validator(Pincode\Pincode::IN);
            if ($pincodeValidator->validate($pincode) === false)
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

    protected static $editRules = [
        Entity::ACCOUNT_NUMBER       => 'filled|string|max:40',
        Entity::ACCOUNT_IFSC         => 'filled|string|size:11',
        Entity::BANK_INTERNAL_STATUS => 'filled|string',
        Entity::STATUS               => 'filled|string|custom',
    ];

    protected static $rblUpdateRules = [
        Entity::ACCOUNT_NUMBER       => 'required_with:account_ifsc|max:40',
        Entity::ACCOUNT_IFSC         => 'required_with:account_number|size:11',
        Entity::STATUS               => 'filled|string|custom',
        Entity::BANK_INTERNAL_STATUS => 'required_if:status,processing,processed,cancelled|string',
    ];

    /**
     * @param string $attribute
     * @param string $status
     *
     * @throws BadRequestValidationFailureException
     */
    protected function validateStatus(string $attribute, string $status)
    {
        if (Status::isValidStatus($status) === false)
        {
            throw new BadRequestValidationFailureException(
                'Banking account status is invalid',
                Entity::STATUS,
                [Entity::STATUS => $status]);
        }
    }
}
