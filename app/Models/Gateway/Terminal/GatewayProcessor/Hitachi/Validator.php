<?php

namespace RZP\Models\Gateway\Terminal\GatewayProcessor\Hitachi;

use RZP\Base;
use RZP\Exception;
use RZP\Gateway\Hitachi\TerminalFields;
use RZP\Models\Currency\Currency;

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
}
