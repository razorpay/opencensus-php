<?php

namespace Models\Customer\Token;

use Models\Bank;
use Models\Base;
use Models\Customer\Token\Entity;

class Validator extends Base\Validator
{
    protected static $createRules = array(
        Entity::METHOD          => 'required|in:card,netbanking,wallet',
        Entity::CARD_ID         => 'required_only_if:method,card|alpha_num|size:14',
        Entity::BANK            => 'required_only_if:method,netbanking',
        Entity::WALLET          => 'required_only_if:method,wallet|in:paytm,mobikwik,payzapp,payumoney',
        Entity::GATEWAY_TOKEN   => 'sometimes|string',
        Entity::GATEWAY_TOKEN2  => 'sometimes|string',
        Entity::EXPIRES_AT      => 'sometimes|integer',
    );

    protected static $createValidators = array(
        Entity::BANK
    );

    protected static function validateBank($input)
    {
        if(empty($input[Entity::BANK]))
        {
            return;
        }

        if(!Bank\IFSC::exists($input[Entity::BANK]))
        {
            throw new Exception\BadRequestValidationFailureException(
                'Invalid bank name in input: '. $input[Entity::BANK]);
        }
    }
}
