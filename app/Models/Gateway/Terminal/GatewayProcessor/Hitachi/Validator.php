<?php

namespace RZP\Models\Gateway\Terminal\GatewayProcessor\Hitachi;

use RZP\Base;
use RZP\Exception;
use RZP\Constants\IndianStates;
use RZP\Models\Merchant\Detail;
use RZP\Models\Currency\Currency;
use RZP\Gateway\Hitachi\TerminalFields;

class Validator extends Base\Validator
{
    protected $allowedTransModes = [
        'CARDS', 
        'UPI', 
        'BharatQR',
    ];

    protected static $gatewayInputRules = [
        TerminalFields::TRANS_MODE => 'required|string|custom',
        TerminalFields::CURRENCY   => 'required|string|custom',
        TerminalFields::MCC        => 'sometimes|string|numeric|digits:4'
    ];

    protected static $merchantDetailInputRules = [
            Detail\Entity::BUSINESS_OPERATION_ADDRESS       => 'required|string',
            Detail\Entity::BUSINESS_OPERATION_STATE         => 'required|string|custom',
            Detail\Entity::BUSINESS_OPERATION_PIN           => 'required|numeric|digits:6',
            Detail\Entity::BUSINESS_DBA                     => 'required|string',
            Detail\Entity::BUSINESS_NAME                    => 'required|string',
            Detail\Entity::BUSINESS_OPERATION_CITY          => 'required|string',
    ];

    protected function validateTransMode($attribute, $value)
    {
        if (in_array($value, $this->allowedTransModes, true) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Onboarding not allowed for this trans mode on Hitachi',
                $attribute,
                [
                    $attribute => $value,
                ]);
        }
    }

    protected function validateCurrencyCode($attribute, $value)
    {
        if (in_array($value, Currency::SUPPORTED_CURRENCIES, true) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Invalid Currency Code',
                $attribute,
                [
                    $attribute => $value,
                ]);
        }
    }

    protected function validateBusinessOperationState($attribute, $value)
    {
        if (IndianStates::stateValueExist($value) === true)
        {
            return;
        }
        if (IndianStates::getStateCode($value) === null)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Invalid State',
                $attribute,
                [
                    $attribute => $value,
                ]);
        }
    }
}
