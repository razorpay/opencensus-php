<?php

namespace RZP\Models\Emi;

use RZP\Models\Base;
use RZP\Exception;
use RZP\Models\Payment\Gateway;

class Validator extends Base\Validator
{
    protected static $createRules = array(
        'bank'                  => 'sometimes|size:4',
        'network'               => 'sometimes|size:5|in:amex',
        'duration'              => 'required|integer|in:3,6,9,12,18,24',
        'rate'                  => 'required|integer',
        'methods'               => 'sometimes|in:card,wallet,netbanking',
        'min_amount'            => 'sometimes|integer'
    );

    protected static $createValidators = array(
        'bank',
    );

    protected function validateBank($input)
    {
        if (in_array($input['bank'], Gateway::$emiBanks) === false)
        {
            throw new Exception\BadRequestValidationFailureException(
                'invalid bank name: '. $input['bank']);
        }
    }
}
