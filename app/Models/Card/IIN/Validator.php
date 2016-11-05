<?php

namespace RZP\Models\Card\IIN;

use RZP\Base;
use RZP\Models\Bank;
use RZP\Models\Card;
use RZP\Models\Card\Network;
use RZP\Error\ErrorCode;
use RZP\Exception;

class Validator extends Base\Validator
{
    protected static $createRules = array(
        Entity::IIN           => 'required|numeric|digits:6',
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
        Entity::NETWORK       => 'sometimes',
        Entity::TYPE          => 'sometimes',
        Entity::COUNTRY       => 'sometimes|size:2',
        Entity::CATEGORY      => 'sometimes',
        Entity::ISSUER        => 'sometimes',
        Entity::TRIVIA        => 'sometimes',
        Entity::ISSUER_NAME   => 'sometimes',
        Entity::EMI           => 'sometimes|integer|in:0,1',
    );

    protected static $createValidators = array(
        'create_network',
        Entity::TYPE,
        Entity::ISSUER,
    );

    protected static $editValidators = array(
        'edit_network',
        Entity::TYPE,
        Entity::ISSUER,
    );

    protected function validateCreateNetwork($input)
    {
        $this->validateNetwork($input, $input[Entity::IIN]);
    }

    protected function validateEditNetwork($input)
    {
        if (isset($input[Entity::NETWORK]) === false)
        {
            return;
        }

        $this->validateNetwork($input, $this->entity->getIin());
    }

    protected function validateNetwork($input, $iin)
    {
        $network = $input[Entity::NETWORK];

        if (Card\Network::isValidNetworkName($network) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Not a valid network name: ' . $input[Entity::NETWORK]);
        }

        $detected = Card\Network::detectNetwork($iin);
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
        if (!isset($input[Entity::TYPE]))
        {
            return;
        }

        if (Card\Type::isValidType($input[Entity::TYPE]) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Not a valid type name: ' . $input[Entity::TYPE]);
        }
    }

    protected function validateIssuer($input)
    {
        if (isset($input[Entity::ISSUER]) === false)
        {
            return;
        }

        if (Bank\IFSC::exists($input[Entity::ISSUER]) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Invalid bank name in input: '. $input[Entity::ISSUER]);
        }
    }
}
