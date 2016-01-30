<?php

namespace Models\Card\IIN;

use Models\Bank;
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
        Entity::ISSUER_NAME   => 'sometimes',
        Entity::EMI           => 'sometimes|integer|in:0,1',
    );

    protected static $editRules = array(
        Entity::COUNTRY       => 'sometimes|size:2',
        Entity::CATEGORY      => 'sometimes',
        Entity::ISSUER        => 'sometimes',
        Entity::TRIVIA        => 'sometimes',
        Entity::ISSUER_NAME   => 'sometimes',
        Entity::EMI           => 'sometimes|integer|in:0,1',
        Entity::NETWORK       => 'required',
        Entity::TYPE          => 'required'
    );

    protected static $createValidators = array(
        Entity::NETWORK,
        Entity::TYPE,
        Entity::ISSUER,
    );

    protected static $editValidators = array(
        Entity::NETWORK,
        Entity::TYPE,
        Entity::ISSUER,
    );

    protected function validateNetwork($input)
    {
        $network = $input[Entity::NETWORK];

        if (Card\Network::isValidNetworkName($network) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Not a valid network name: ' . $input['network']);
        }

        $detected = Card\Network::detectNetwork($input[Entity::IIN]);
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
        if (Card\Type::isValidType($input[Entity::TYPE]) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Not a valid type name: ' . $input[Entity::TYPE]);
        }
    }

    protected function validateIssuer($input)
    {
        if(!isset($input[Entity::ISSUER]))
        {
            return;
        }

        if(!Bank\IFSC::exists($input[Entity::ISSUER]))
        {
            throw new Exception\BadRequestValidationFailureException(
                'Invalid bank name in input: '. $bank);
            
        }
    }
}
