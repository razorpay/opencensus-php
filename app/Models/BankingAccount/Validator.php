<?php

namespace RZP\Models\BankingAccount;

use RZP\Base;
use RZP\Models\Pincode;
use RZP\Exception\BadRequestValidationFailureException;

class Validator extends Base\Validator
{
    const PRE_PROCESS           = 'pre_process';
    const INTERNAL_EDIT         = 'internal_edit';
    const PROCESSED_STATUS      = 'processed_status';
    const SERVICEABLE_PINCODE   = 'serviceable_pincode';
    const YESBANK_CREATE        = 'yesbank_create';
    const INTERNAL_EDIT_STATUS  = 'internal_edit_status';

    protected static $preProcessRules = [
        Entity::CHANNEL => 'required|string|custom',
    ];

    protected static $yesbankCreateRules = [
        Entity::ACCOUNT_NUMBER      => 'required|string|between:5,40',
        Entity::ACCOUNT_IFSC        => 'required|string|size:11',
        Entity::FTS_FUND_ACCOUNT_ID => 'sometimes|nullable|string|size:14',
        Entity::ACCOUNT_TYPE        => 'required|string|in:nodal',
    ];

    protected static $rblCreateRules = [
        Entity::CHANNEL               => 'required|string|custom',
        Entity::BANK_REFERENCE_NUMBER => 'required|integer|digits:5',
        Entity::PINCODE               => 'required|string',
    ];

    protected static $createRules = [
        Entity::CHANNEL               => 'required|string|custom',
        Entity::BANK_REFERENCE_NUMBER => 'required_if:channel,rbl',
        Entity::PINCODE               => 'required_if:channel,rbl',
        Entity::ACCOUNT_IFSC          => 'sometimes|nullable|string|size:11',
        Entity::FTS_FUND_ACCOUNT_ID   => 'sometimes|nullable|string|size:14',
        Entity::ACCOUNT_TYPE          => 'required|string|custom',
        Entity::ACCOUNT_NUMBER        => 'sometimes|nullable|string|max:40',
    ];

    protected static $editRules = [
        Entity::ACCOUNT_NUMBER                  => 'filled|alpha_num|between:5,40',
        Entity::ACCOUNT_IFSC                    => 'filled|alpha_num|size:11',
        Entity::BANK_INTERNAL_STATUS            => 'sometimes|string',
        Entity::STATUS                          => 'filled|string|custom',
        Entity::BANK_REFERENCE_NUMBER           => 'filled|string',
        Entity::BANK_INTERNAL_REFERENCE_NUMBER  => 'filled|string',
        Entity::BENEFICIARY_PIN                 => 'filled|string',
        Entity::BENEFICIARY_CITY                => 'filled|string',
        Entity::BENEFICIARY_COUNTRY             => 'filled|string',
        Entity::BENEFICIARY_STATE               => 'filled|string',
        Entity::ACCOUNT_ACTIVATION_DATE         => 'filled|integer',
        Entity::BENEFICIARY_ADDRESS1            => 'filled|string',
        Entity::BENEFICIARY_ADDRESS2            => 'filled|string',
        Entity::BENEFICIARY_ADDRESS3            => 'filled|string',
        Entity::BENEFICIARY_MOBILE              => 'filled|string',
        Entity::BENEFICIARY_EMAIL               => 'filled|string',
        Entity::BENEFICIARY_NAME                => 'filled|string',
        Entity::USERNAME                        => 'filled|string',
        Entity::PASSWORD                        => 'filled|string',
        Entity::REFERENCE1                      => 'filled|string',
    ];

    protected static $internalEditRules = [
        Entity::ACCOUNT_NUMBER                  => 'filled|alpha_num|between:5,40',
        Entity::ACCOUNT_IFSC                    => 'filled|alpha_num|size:11',
        Entity::STATUS                          => 'filled|string',
        Entity::BENEFICIARY_PIN                 => 'filled|string',
        Entity::BENEFICIARY_CITY                => 'filled|string',
        Entity::BENEFICIARY_COUNTRY             => 'filled|string',
        Entity::BENEFICIARY_STATE               => 'filled|string',
        Entity::ACCOUNT_ACTIVATION_DATE         => 'filled|integer',
        Entity::BENEFICIARY_ADDRESS1            => 'filled|string',
        Entity::BENEFICIARY_ADDRESS2            => 'filled|string',
        Entity::BENEFICIARY_ADDRESS3            => 'filled|string',
        Entity::BENEFICIARY_MOBILE              => 'filled|string',
        Entity::BENEFICIARY_EMAIL               => 'filled|string',
        Entity::BENEFICIARY_NAME                => 'filled|string',
    ];

    protected static $internalEditValidators = [
        self::INTERNAL_EDIT_STATUS,
    ];

    // TODO: handle cases when some fields are already present in the model when the status is processed.
    protected static $processedStatusRules = [
        Entity::ACCOUNT_NUMBER                  => 'required|alpha_num|between:5,40',
        Entity::ACCOUNT_IFSC                    => 'required|alpha_num|size:11',
        Entity::STATUS                          => 'required|string|custom',
        Entity::BENEFICIARY_PIN                 => 'required|string',
        Entity::BENEFICIARY_CITY                => 'required|string',
        Entity::BENEFICIARY_COUNTRY             => 'required|string',
        Entity::BENEFICIARY_STATE               => 'required|string',
        Entity::ACCOUNT_ACTIVATION_DATE         => 'required|integer',
        Entity::BENEFICIARY_ADDRESS1            => 'required|string',
        Entity::BENEFICIARY_ADDRESS2            => 'required|string',
        Entity::BENEFICIARY_ADDRESS3            => 'required|string',
        Entity::BENEFICIARY_MOBILE              => 'required|string',
        Entity::BENEFICIARY_EMAIL               => 'required|string',
        Entity::BENEFICIARY_NAME                => 'required|string',
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
        Status::validate($status);
    }

    protected function validateAccountType(string $attribute, string $accountType)
    {
        if (AccountType::isValid($accountType) === false)
        {
            throw new BadRequestValidationFailureException(
                'Banking account type is invalid',
                Entity::ACCOUNT_TYPE,
                [Entity::ACCOUNT_TYPE => $accountType]);
        }
    }

    protected function validateInternalEditStatus(array $input)
    {
        if (isset($input[Entity::STATUS]) === true)
        {
            $status = $input[Entity::STATUS];

            if (in_array($status, Status::$internallyEditStatuses, true) === false)
            {
                throw new BadRequestValidationFailureException(
                    'Given status is not an allowed status',
                    Entity::STATUS,
                    [
                        Entity::STATUS => $status,
                    ]);
            }
        }
    }
}
