<?php

namespace RZP\Models\BankingAccount;

use RZP\Base;
use RZP\Models\Pincode;
use RZP\Exception\BadRequestValidationFailureException;

class Validator extends Base\Validator
{
    protected static $preCreateRules = [
        Entity::CHANNEL => 'required|string|custom',
    ];

    protected static $createRules = [
        Entity::CHANNEL => 'required|string|custom',
        Entity::PINCODE => 'required_if:channel,rbl',
        Entity::STATUS  => 'required|custom',
    ];

    protected static $editRules = [
        Entity::ACCOUNT_NUMBER                  => 'filled|alpha_num|max:40',
        Entity::ACCOUNT_IFSC                    => 'filled|alpha_num|size:11',
        Entity::BANK_INTERNAL_STATUS            => 'sometimes|string',
        Entity::STATUS                          => 'filled|string|custom',
        Entity::BANK_REFERENCE_NUMBER           => 'filled|string|size:5',
        Entity::BANK_INTERNAL_REFERENCE_NUMBER  => 'filled|string',
        Entity::PINCODE                         => 'filled|integer|digits:6',
        Entity::BENEFICIARY_CITY                => 'filled|string',
        Entity::BENEFICIARY_COUNTRY             => 'filled|string',
        Entity::BENEFICIARY_STATE               => 'filled|string',
        Entity::ACCOUNT_ACTIVATION_DATE         => 'filled|string|date',
        Entity::BENEFICIARY_ADDRESS1            => 'filled|string',
        Entity::BENEFICIARY_ADDRESS2            => 'filled|string',
        Entity::BENEFICIARY_ADDRESS3            => 'filled|string',
        Entity::BENEFICIARY_MOBILE              => 'filled|string',
        Entity::BENEFICIARY_EMAIL               => 'filled|string',
        Entity::BENEFICIARY_NAME                => 'filled|string',
    ];

    protected static $serviceablePincodeRules = [
        Entity::CHANNEL         => 'required|string|custom',
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

    protected function validateChannel($attribute, $channel)
    {
        Channel::validateChannel($channel);
    }

    /**
     * @param string $attribute
     * @param string $status
     *
     * @throws BadRequestValidationFailureException
     */
    protected function validateStatus(string $attribute, string $status = null)
    {
        if (Status::validate($status) === false)
        {
            throw new BadRequestValidationFailureException(
                'Banking account status is invalid',
                Entity::STATUS,
                [Entity::STATUS => $status]);
        }
    }
}
