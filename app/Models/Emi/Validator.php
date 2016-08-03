<?php

namespace RZP\Models\Emi;

use RZP\Models\Base;
use RZP\Exception;
use RZP\Models\Payment\Gateway;

class Validator extends Base\Validator
{
    protected static $createRules = array(
        'bank'                  => 'required|size:4',
        'duration'              => 'required|integer|in:3,6,9,12,18,24',
        'rate'                  => 'required|integer',
        'methods'               => 'sometimes|in:card,wallet,netbanking',
        'min_amount'            => 'sometimes|integer'
    );

    protected static $createValidators = array(
        'bank'
    );

    protected function validateBank($input)
    {

    }
}
