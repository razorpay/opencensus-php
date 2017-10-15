<?php

namespace RZP\Models\EMandate;

use RZP\Base;
use RZP\Exception;
use RZP\Models\Payment\Gateway;

class Validator extends Base\Validator
{
    public function validateRegistrationGateway($gateway)
    {
        $validGateways = array_values(Gateway::$fileBasedEMandateRegistrationBanks);

        if (in_array($gateway, $validGateways, true) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Invalid eMandate registration gateway. ' . $gateway);
        }
    }

    public function validateDebitGateway($gateway)
    {
        $validGateways = array_values(Gateway::$fileBasedEMandateDebitBanks);

        if (in_array($gateway, $validGateways, true) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Invalid eMandate debit gateway. ' . $gateway);
        }
    }
}