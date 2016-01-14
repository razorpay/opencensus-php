<?php

namespace Models\Emi;

use EE\Exception;
use Models\Base;

class Validator extends Base\Validator
{
    protected static $createRules = array(
        'bank'                  => 'required|size:4',
        'duration'              => 'required|integer|min:1|max:12',
        'rate'                  => 'required|integer',
        'methods'                => 'sometimes|in:debitcard,creditcard,wallet,netbanking',
    );
}
