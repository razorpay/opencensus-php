<?php

namespace Models\Emi;

use EE\Exception;
use Models\Base;

class Validator extends Base\Validator
{
    protected static $createRules = array(
        'bank'                  => 'required|size:4|in:HDFC,UTIB',
        'duration'              => 'required|integer|in:3,6,9,12,15',
        'rate'                  => 'required|integer',
        'methods'               => 'sometimes|in:card,wallet,netbanking',
    );
}
