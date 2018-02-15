<?php

namespace RZP\Models\Customer\Token;

use Carbon\Carbon;
use RZP\Base;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\Bank;
use RZP\Models\Card;
use RZP\Models\Payment\Processor\Wallet;

class Validator extends Base\Validator
{
    const CREATE_DIRECT = 'create_direct';

    protected static $createRules = [
        Entity::METHOD              => 'required|in:card,netbanking,wallet',
        Entity::CARD_ID             => 'required_only_if:method,card|alpha_num|size:14',
        Entity::BANK                => 'required_only_if:method,netbanking|custom',
        // We generate it if max_amount is not present
        Entity::MAX_AMOUNT          => 'sometimes_if:method,netbanking',
        Entity::WALLET              => 'required_only_if:method,wallet|custom',
        Entity::RECURRING           => 'sometimes|boolean',
        Entity::GATEWAY_TOKEN       => 'sometimes|string',
        Entity::GATEWAY_TOKEN2      => 'sometimes|string',
        // We generate it if expired_at is not present and method is netbanking
        Entity::EXPIRED_AT          => 'sometimes|epoch|nullable|custom',
        Entity::ACCOUNT_NUMBER      => 'sometimes|alpha_num|between:5,20',
        Entity::BENEFICIARY_NAME    => 'sometimes|alpha_space_num|between:4,120',
        Entity::IFSC                => 'sometimes|alpha_num|size:11',
    ];

    protected static $createDirectRules = [
        Entity::CARD            => 'required|array',
        Entity::METHOD          => 'required|in:card'
    ];

    protected static $editRules = [
        Entity::RECURRING       => 'sometimes|in:0',
    ];

    protected static function validateBank($attribute, $value)
    {
        if (Bank\IFSC::exists($value) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Invalid bank name in input: '. $value);
        }
    }

    protected function validateWallet($attribute, $value)
    {
        if (Wallet::exists($value) === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PAYMENT_WALLET_NOT_SUPPORTED);
        }
    }

    protected function validateExpiredAt($attribute, $value)
    {
        if (empty($value) === false)
        {
            $currentTime = Carbon::now()->getTimestamp();

            if ($value <= $currentTime)
            {
                throw new Exception\BadRequestValidationFailureException(
                    'Expiry time should be greater than the current time',
                    null,
                    [
                        'expired_at'    => $value,
                        'id'            => $this->entity->getId(),
                    ]);
            }
        }
    }
}

