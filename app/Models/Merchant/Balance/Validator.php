<?php

namespace RZP\Models\Merchant\Balance;

use RZP\Base;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\BankingAccount\Channel;
use RZP\Models\Payout\Mode as PayoutMode;

class Validator extends Base\Validator
{
    const LOCKED_BALANCE = 'locked_balance';

    const UPDATE_FREE_PAYOUTS_ATTRIBUTES = 'update_free_payouts_attributes';

    protected static $createRules = [
        Entity::CURRENCY         => 'required|string|custom',
        Entity::TYPE             => 'required|string|custom',
        Entity::ACCOUNT_TYPE     => 'filled|string|custom',
        Entity::CHANNEL          => 'sometimes|string|nullable|custom',
        Entity::ACCOUNT_NUMBER   => 'sometimes|string|nullable',
    ];

    public static $createCapitalBalanceInputRules = [
        Entity::MERCHANT_ID      => 'required|alpha_num|size:14',
        Entity::CURRENCY         => 'required|string|in:INR',
        Entity::BALANCE          => 'sometimes|int|min:0',
        Entity::TYPE             => 'required|string|in:principal,interest,charge',
    ];

    protected static $lockedBalanceRules = [
        Entity::LOCKED_BALANCE => 'required|int|min:0',
    ];

    protected static $updateFreePayoutsAttributesRules = [
        FreePayout::FREE_PAYOUTS_COUNT                => 'filled|int|min:0',
        FreePayout::FREE_PAYOUTS_SUPPORTED_MODES      => 'sometimes|array|custom',
    ];

    protected static $updateFreePayoutsAttributesValidators = [
        'updateFreePayoutsAttributes'
    ];

    protected function validateUpdateFreePayoutsAttributes($input)
    {
        if ((isset($input[FreePayout::FREE_PAYOUTS_COUNT]) === false) and
            (isset($input[FreePayout::FREE_PAYOUTS_SUPPORTED_MODES]) === false))
        {
            $message = sprintf("Either one of %s or %s or both should be given.",
                               FreePayout::FREE_PAYOUTS_COUNT,
                               FreePayout::FREE_PAYOUTS_SUPPORTED_MODES);

            throw new Exception\BadRequestValidationFailureException(
                $message,
                null,
                [
                    'input' => $input,
                ]);
        }
    }

    protected function validateFreePayoutsSupportedModes($attribute, $values)
    {
        $uniqueValues = [];

        foreach ($values as $value)
        {
            if (in_array($value, $uniqueValues, true) === true)
            {
                throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_FREE_PAYOUT_SUPPORTED_MODES_ARRAY_DUPLICATE_VALUE,
                    null,
                    [
                        $attribute      => $values,
                        'duplicate_value' => $value
                    ]);
            }

            PayoutMode::validateMode($value);

            array_push($uniqueValues, $value);
        }
    }

    protected function validateType($attribute, $type)
    {
        if (Type::exists($type) === false)
        {
            throw new Exception\BadRequestValidationFailureException('Invalid type name: ' . $type);
        }
    }

    protected function validateCurrency($attribute, $value)
    {
        if($this->entity->merchant->getCurrency() !== $value) {
            throw new Exception\BadRequestValidationFailureException('Balance and Merchant currency mismatch, balance_currency => ' . $value);
        }
    }

    protected function validateAccountType($attribute, $accType)
    {
        if (AccountType::exists($accType) === false)
        {
            throw new Exception\BadRequestValidationFailureException('Invalid account type:' . $accType);
        }
    }

    protected function validateChannel($attribute, $channel)
    {
        if (Channel::validateChannel($channel) === false)
        {
            throw new Exception\BadRequestValidationFailureException('Invalid channel:' . $channel);
        }
    }
}

