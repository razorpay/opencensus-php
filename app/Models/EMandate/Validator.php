<?php

namespace RZP\Models\EMandate;

use RZP\Base;
use RZP\Exception;

class Validator extends Base\Validator
{
    public function validateRegistrationGateway($gateway)
    {
        $validGateways = array_values(Gateway::$fileBasedEMandateRegistrationBanks);

        if (in_array($gateway, $validGateways, true) === false)
        {
            throw new Exception\BadRequestValidationFailureException('Invalida eMandate gateway. ' . $gateway);
        }
    }
}