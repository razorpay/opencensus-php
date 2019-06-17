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

    protected static $rblAvailabilityRules = [
        Entity::CHANNEL => 'required|string|custom',
        Entity::PINCODE => 'required_if:channel,rbl',
    ];

    protected static $createRules = [
        Entity::CHANNEL => 'required|string|custom',
        Entity::PINCODE => 'required_if:channel,rbl',
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

    protected static $rblUpdateRules = [
        Entity::ACCOUNT_NUMBER                  => 'required_with:account_ifsc|max:40',
        Entity::ACCOUNT_IFSC                    => 'required_with:account_number|size:11',
        Entity::STATUS                          => 'filled|string|custom',
        Entity::BANK_INTERNAL_STATUS            => 'required_if:status,processing,processed,cancelled|string',
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
        Entity::BENEFICIARY_NAME                => 'filled|string',
        Entity::BENEFICIARY_MOBILE              => 'filled|string',
        Entity::BENEFICIARY_EMAIL               => 'filled|string',
    ];

    // ToDo fix the validator on seeing actual data types in RBL notification
    protected static $rblAccountInfoNotificationRules = [
        RblFields::ACCT_NAME         => 'required|string',
        RblFields::FORACID           => 'required|string',
        RblFields::IFSC              => 'required|alpha_num|size:11',
        RblFields::PINCODE           => 'required|integer|digits:6',
        RblFields::ADDR_1            => 'required|string',
        RblFields::ADDR_2            => 'required|string',
        RblFields::ADDR_3            => 'required|string',
        RblFields::CIF_ID            => 'required|string',
        RblFields::CITY              => 'required|string',
        RblFields::STATE             => 'required|string',
        RblFields::COUNTRY           => 'required|string',
        RblFields::REF_NUM_1         => 'required|string|size:5',
        RblFields::ACTIVATION_DATE   => 'required|string',
        RblFields::PHONE_NUM         => 'required|string',
        RblFields::EMAIL_ID          => 'required|string',
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
