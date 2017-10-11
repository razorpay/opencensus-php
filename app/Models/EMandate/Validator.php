<?php

namespace RZP\Models\EMandate;

use RZP\Base;
use RZP\Exception;

class Validator extends Base\Validator
{
    public function validateGateway($gateway)
    {
        $fileBasedEMandateBanks = array_values(Payment\Gateway::$fileBasedEMandateBanks);

        if (in_array($gateway, $fileBasedEMandateBanks, true) === false)
        {
            throw new Exception\BadRequestValidationFailureException('Invalida eMandate gateway. ' . $gateway);
        }
    }
}