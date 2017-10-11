<?php

namespace RZP\Models\Customer\Token;

use RZP\Base;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\Bank;
use RZP\Models\Card;
use RZP\Models\Payment\Processor\Wallet;

class Validator extends Base\Validator
{
    protected static $createRules = array(
        Entity::METHOD          => 'required|in:card,netbanking,wallet',
        Entity::CARD_ID         => 'required_only_if:method,card|alpha_num|size:14',
        Entity::BANK            => 'required_only_if:method,netbanking|custom',
        Entity::MAX_AMOUNT      => 'required_only_if:method,netbanking|required_unless:method,card,wallet',
        Entity::WALLET          => 'required_only_if:method,wallet|custom',
        Entity::RECURRING       => 'sometimes|boolean',
        Entity::GATEWAY_TOKEN   => 'sometimes|string',
        Entity::GATEWAY_TOKEN2  => 'sometimes|string',
        Entity::EXPIRED_AT      => 'sometimes|integer',
    );

    protected static $editRules = array(
        Entity::RECURRING       => 'sometimes|in:0',
    );

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
}

