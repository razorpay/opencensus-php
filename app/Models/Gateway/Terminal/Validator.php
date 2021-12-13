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
        Payment\Gateway::FULCRUM,
        Payment\Gateway::UPI_HULK,
        Payment\Gateway::WORLDLINE,
    ];

    protected static $merchantOnboardRules = [
        Terminal\Entity::GATEWAY    => 'required|string|custom',
        Service::GATEWAY_INPUT      => 'required|array',
    ];

    protected function validateGateway($attribute, $value)
    {
        if (in_array($value, $this->onboardAllowedGateways, true) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Invalid Gateway',
                $attribute,
                [
                    $attribute => $value,
                ]);
        }
    }
}
