<?php

namespace RZP\Models\Card\IIN;

use RZP\Base;
use RZP\Exception;
use RZP\Models\Bank;
use RZP\Models\Card;

class Validator extends Base\Validator
{
    protected static $createRules = array(
        Entity::IIN           => 'required|numeric|digits:6',
        Entity::NETWORK       => 'required',
        Entity::TYPE          => 'required',
        Entity::COUNTRY       => 'sometimes|nullable|size:2',
        Entity::CATEGORY      => 'sometimes',
        Entity::ISSUER        => 'required_if:emi,1',
        Entity::TRIVIA        => 'sometimes',
        Entity::ISSUER_NAME   => 'sometimes',
        Entity::EMI           => 'sometimes|integer|in:0,1',
        Entity::ENABLED       => 'sometimes|integer|in:0,1',
        Entity::FLOWS         => 'sometimes|array|custom',
    );

    protected static $editRules = array(
        Entity::NETWORK       => 'sometimes',
        Entity::TYPE          => 'sometimes',
        Entity::COUNTRY       => 'sometimes|nullable|size:2',
        Entity::CATEGORY      => 'sometimes',
        Entity::ISSUER        => 'required_if:emi,1',
        Entity::TRIVIA        => 'sometimes',
        Entity::ISSUER_NAME   => 'sometimes',
        Entity::EMI           => 'sometimes|integer|in:0,1',
        Entity::ENABLED       => 'sometimes|integer|in:0,1',
        Entity::FLOWS         => 'sometimes|array|filled|custom',
    );

    protected static $createValidators = array(
        'create_network',
        Entity::TYPE,
        Entity::ISSUER,
    );

    protected static $fetchPaymentFlowsRules = [
        Entity::IIN           => 'required|numeric|digits:6',
    ];

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

    protected function validateFlows($attribute, $flows)
    {
        $validFlows = Flow::getValid();

        foreach ($flows as $flow => $value)
        {
            if (in_array($flow, $validFlows) === false)
            {
                throw new Exception\BadRequestValidationFailureException(
                    'Invalid flow in input: ' . $flow);
            }
        }
    }
}
