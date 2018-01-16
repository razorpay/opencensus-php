<?php

namespace RZP\Models\EMandate;

use RZP\Base;
use RZP\Exception;
use RZP\Models\Payment\Gateway;

class Validator extends Base\Validator
{
    public function validateDebitGateway($gateway)
    {
        if (Gateway::isFileBasedEMandateDebitGateway($gateway) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Invalid eMandate debit gateway. ' . $gateway);
        }
    }
}
