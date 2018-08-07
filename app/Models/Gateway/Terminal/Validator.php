<?php

namespace RZP\Models\Gateway\Terminal;

use RZP\Base;
use RZP\Exception;
use RZP\Models\Payment;
use RZP\Models\Terminal;

class Validator extends Base\Validator
{
    protected $onboardAllowedGateways = [
        Payment\Gateway::HITACHI,
        Payment\Gateway::UPI_HULK,
    ];

    protected static $merchantOnboardRules = [
        Terminal\Entity::GATEWAY                        => 'required|string|custom',
        Service::GATEWAY_INPUT                          => 'required|array',
        Service::TERMINAL                               => 'required|array',
        Service::TERMINAL.'.'.Service::PG_MERCHANT_ID   => 'required|alpha_num|exists:merchants,id',
    ];

    protected function validateGateway($attribute, $value)
    {
        if (in_array($value, $this->onboardAllowedGateways, true) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Onboarding not allowed on gateway',
                $attribute,
                [
                    $attribute => $value,
                ]);
        }
    }
}
