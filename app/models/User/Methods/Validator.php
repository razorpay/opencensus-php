<?php

namespace Models\User\Methods;

use Models\Base;
use Models\User\Methods\Entity;

class Validator extends Public\Validator
{
    protected static $createRules = array(
        Entity::USER_ID         => 'required|unique:users',
        Entity::METHOD          => 'required|in:card,netbanking,wallet',
        Entity::CARD_ID         => 'required_only_if:method,card|unique:cards',
        Entity::BANK            => 'required_only_if:method,netbanking',
        Entity::WALLET          => 'required_only_if:method,wallet|in:paytm,mobikwik,payzapp',
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
