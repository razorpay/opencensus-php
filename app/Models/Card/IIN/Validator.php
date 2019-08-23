<?php

namespace RZP\Models\Card\IIN;

use RZP\Base;
use RZP\Exception;
use RZP\Models\Bank;
use RZP\Models\Card;

class Validator extends Base\Validator
{
    protected static $createRules = array(
        Entity::IIN            => 'required|digits:6',
        Entity::NETWORK        => 'required',
        Entity::TYPE           => 'required',
        Entity::COUNTRY        => 'sometimes|nullable|size:2',
        Entity::CATEGORY       => 'sometimes',
        Entity::ISSUER         => 'sometimes',
        Entity::TRIVIA         => 'sometimes',
        Entity::ISSUER_NAME    => 'sometimes',
        Entity::EMI            => 'sometimes|integer|in:0,1',
        Entity::ENABLED        => 'sometimes|integer|in:0,1',
        Entity::FLOWS          => 'sometimes|array|custom',
        Entity::MESSAGE_TYPE   => 'sometimes|string|custom',
        Entity::RECURRING      => 'sometimes|integer|in:0,1',
    );

    protected static $editRules = array(
        Entity::NETWORK        => 'sometimes',
        Entity::TYPE           => 'sometimes',
        Entity::COUNTRY        => 'sometimes|nullable|size:2',
        Entity::CATEGORY       => 'sometimes',
        Entity::ISSUER         => 'sometimes',
        Entity::TRIVIA         => 'sometimes',
        Entity::ISSUER_NAME    => 'sometimes',
        Entity::EMI            => 'sometimes|integer|in:0,1',
        Entity::ENABLED        => 'sometimes|integer|in:0,1',
        Entity::FLOWS          => 'sometimes|array|filled|custom',
        Entity::LOCKED         => 'sometimes|integer|in:0,1',
        Entity::MESSAGE_TYPE   => 'sometimes|string|custom',
        Entity::RECURRING      => 'sometimes|integer|in:0,1',
    );

    protected static $editBulkRules = [
        Entity::IINS        => 'required|array',
        Entity::IINS . '.*' => 'numeric|digits:6',
        Entity::PAYLOAD     => 'required|array',
    ];

    protected static $binIssuerValidationRules = [
        Entity::NUMBER       => 'required|numeric|digits_between:6,19',
    ];

    protected static $createValidators = [
        'create_network',
        Entity::TYPE,
        Entity::ISSUER,
    ];

    protected static $editValidators = [
        'edit_network',
        Entity::TYPE,
        Entity::ISSUER,
    ];

    protected static $binListValidationRules = [
        Entity::FlOW             => 'required|string|in:otp',
    ];

    protected static $iinBatchFileRules = [
        'file'              => 'required|file',
        'type'              => 'required|custom',
    ];

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

        $this->validateNetwork($input, $this->entity->getIin(), true);
    }

    /**
     * @param $input
     * @param $iin
     * @param bool $skipNetworkRegexValidation - This parameter is for the caller to decide whether an exception
     *             should be raised in the following situation.
     *             Situation is when network in input does not match with the network that corresponds to the regexes
     *             defined in $networkRegexes in Models/Card/Network.php
     * @throws Exception\BadRequestValidationFailureException
     */
    protected function validateNetwork($input, $iin, $skipNetworkRegexValidation = false)
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
            if ($skipNetworkRegexValidation === false)
            {
                throw new Exception\BadRequestValidationFailureException(
                    'Card network given does not match the regex one: ' . $fullName);
            }

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

        foreach ($flows as $flow => $_value)
        {
            if (in_array($flow, $validFlows) === false)
            {
                throw new Exception\BadRequestValidationFailureException(
                    'Invalid flow in input: ' . $flow);
            }
        }
    }

    protected function validateMessageType($attribute, $value)
    {
        if (MessageType::isValid($value) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Invalid Message type given',
                $attribute,
                [
                    $attribute => $value,
                ]);
        }
    }
}
