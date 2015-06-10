<?php

namespace Models\Card\IIN;

use Models\Base;
use Models\Card;
use Models\Card\Network;
use EE\Error\ErrorCode;
use EE\Exception;

class Validator extends Base\Validator
{
    protected static $createRules = array(
        Entity::IIN           => 'required|numeric|digits:6|unique:iins,iin',
        Entity::NETWORK       => 'required',
        Entity::TYPE          => 'required',
        Entity::COUNTRY       => 'sometimes|size:2',
        Entity::CATEGORY      => 'sometimes',
        Entity::ISSUER        => 'sometimes',
        Entity::TRIVIA        => 'sometimes',
    );

    protected static $createValidators = array(
        'network',
        'type');

    protected function validateNetwork($input)
    {
        $network = $input['network'];

        if (Card\Network::isValidNetworkName($network) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Not a valid network name: ' . $input['network']);
        }

        $detected = Card\Network::detectNetwork($input['iin']);
        $fullName = Card\Network::getFullName($detected);

        if (($fullName !== 'Unknown') and
            ($fullName !== $network))
        {
            throw new Exception\BadRequestValidationFailureException(
                'Card network given does not match the regex one: ' . $fullName);
        }
    }

    protected function validateType($input)
    {
        if (Card\Type::isValidType($input['type']) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Not a valid type name: ' . $input['type']);
        }
    }
}
