<?php

namespace RZP\Models\Card\IIN;

use RZP\Base;
use RZP\Exception;
use RZP\Models\Bank;
use RZP\Models\Card;

class Validator extends Base\Validator
{
    protected static $createRules = array(
        Entity::IIN                => 'required|string|regex:/^[0-9]{6}$/',
        Entity::NETWORK            => 'required',
        Entity::TYPE               => 'required',
        Entity::SUBTYPE            => 'filled|string|custom',
        Entity::PRODUCT_CODE       => 'sometimes',
        Entity::COUNTRY            => 'sometimes|nullable|size:2',
        Entity::CATEGORY           => 'sometimes',
        Entity::ISSUER             => 'sometimes',
        Entity::TRIVIA             => 'sometimes',
        Entity::ISSUER_NAME        => 'sometimes',
        Entity::COBRANDING_PARTNER => 'sometimes',
        Entity::EMI                => 'sometimes|integer|in:0,1',
        Entity::ENABLED            => 'sometimes|integer|in:0,1',
        Entity::FLOWS              => 'sometimes|array|custom',
        Entity::MESSAGE_TYPE       => 'sometimes|string|custom',
        Entity::RECURRING          => 'sometimes|integer|in:0,1',
        Entity::MANDATE_HUBS       => 'sometimes|array|custom',
    );

    protected static $editRules = array(
        Entity::NETWORK            => 'required_with:category',
        Entity::TYPE               => 'required_with:category',
        Entity::SUBTYPE            => 'filled|string|custom',
        Entity::PRODUCT_CODE       => 'sometimes',
        Entity::COUNTRY            => 'sometimes|nullable|size:2',
        Entity::CATEGORY           => 'sometimes',
        Entity::ISSUER             => 'sometimes',
        Entity::TRIVIA             => 'sometimes',
        Entity::ISSUER_NAME        => 'sometimes',
        Entity::COBRANDING_PARTNER => 'sometimes',
        Entity::EMI                => 'sometimes|integer|in:0,1',
        Entity::ENABLED            => 'sometimes|integer|in:0,1',
        Entity::FLOWS              => 'sometimes|array|filled|custom',
        Entity::LOCKED             => 'sometimes|integer|in:0,1',
        Entity::MESSAGE_TYPE       => 'sometimes|string|custom',
        Entity::RECURRING          => 'sometimes|integer|in:0,1',
        Entity::MANDATE_HUBS       => 'sometimes|array|filled|custom',
    );

    protected static $editBulkRules = [
        Entity::IINS        => 'required|array',
        Entity::IINS . '.*' => 'numeric|digits:6',
        Entity::PAYLOAD     => 'required|array',
    ];

    protected static $binIssuerValidationRules = [
        Entity::NUMBER       => 'required|numeric|digits_between:6,19',
    ];

    protected static $getIinDetailsRules = [
        'callback'                  => 'sometimes', // JSONP
        'iin'                       => 'required|numeric|digits:6',
        '_'                         => 'sometimes|array',
        'order_id'                  => 'sometimes|filled',
        'language_code'             => 'sometimes',
    ];

    protected static $createValidators = [
        'create_network',
        Entity::TYPE,
        Entity::CATEGORY,
        Entity::ISSUER,
        Entity::COUNTRY,
        Entity::COBRANDING_PARTNER,
    ];

    protected static $editValidators = [
        'edit_network',
        Entity::TYPE,
        Entity::CATEGORY,
        Entity::ISSUER,
        Entity::COUNTRY,
        Entity::COBRANDING_PARTNER,
    ];

    protected static $binListValidationRules = [
        Entity::FlOW             => 'sometimes|string|in:otp',
        Entity::SUBTYPE          => 'sometimes|string|in:business',
    ];

    protected static $iinBatchFileRules = [
        'file'              => 'required|file',
        'type'              => 'required|custom',
    ];

    protected static $fetchIinRules = [
        Entity::IIN            => 'required|numeric|digits_between:6,9',
    ];

    protected function validateCreateNetwork($input)
    {
        $this->validateNetwork($input, $input[Entity::IIN]);
    }

    protected function validateCategory($input)
    {
        if (isset($input[Entity::CATEGORY]) === false)
        {
            return;
        }

        $subtype = Card\SubType::CONSUMER;

        if (isset($input[Entity::SUBTYPE]) === true)
        {
            $subtype = $input[Entity::SUBTYPE];
        }

        if (Category::isValidIinCategory($input[Entity::NETWORK], $input[Entity::TYPE], $subtype,
                $input[Entity::CATEGORY]) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Not a valid category: ' . $input[Entity::CATEGORY]);
        }
    }

    protected function validateCobrandingPartner($input)
    {
        if (!isset($input[Entity::COBRANDING_PARTNER]))
        {
            return;
        }

        if (Card\CobrandingPartner::isValid($input[Entity::COBRANDING_PARTNER]) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Not a valid cobranding parter: ' . $input[Entity::COBRANDING_PARTNER]);
        }
    }

    protected function validateEditNetwork($input)
    {
        if (isset($input[Entity::NETWORK]) === false)
        {
            return;
        }

        $this->validateNetwork($input, $this->entity->getIin());
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
    protected function validateNetwork($input, $iin)
    {
        $network = $input[Entity::NETWORK];

        if (Card\Network::isValidNetworkName($network) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Not a valid network name: ' . $input[Entity::NETWORK]);
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

    protected function validateSubType($attribute, $subtype)
    {
        Card\SubType::checkSubType($subtype);
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

    protected function validateMandateHubs($attribute, $mandateHubs)
    {
        $validMandateHubs = MandateHub::getValid();

        foreach ($mandateHubs as $mandateHub => $_value)
        {
            if (in_array($mandateHub, $validMandateHubs) === false)
            {
                throw new Exception\BadRequestValidationFailureException(
                    'Invalid mandate_hub in input: ' . $mandateHub);
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

    protected function validateCountry($input)
    {
        if (isset($input[Entity::COUNTRY]) === false)
        {
            return;
        }
        if (Country::isValid($input[Entity::COUNTRY]) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Invalid Country Code: '.$input[Entity::COUNTRY]);
        }
    }
}
