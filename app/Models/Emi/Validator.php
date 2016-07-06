<?php

namespace RZP\Models\Emi;

use EE\Exception;
use RZP\Models\Base;

class Validator extends Base\Validator
{
    protected static $createRules = array(
        'bank'                  => 'required|size:4|in:HDFC,UTIB,KKBK',
        'duration'              => 'required|integer|in:3,6,9,12,18,24',
        'rate'                  => 'required|integer',
        'methods'               => 'sometimes|in:card,wallet,netbanking',
    );
}
