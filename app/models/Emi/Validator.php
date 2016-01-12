<?php

namespace Models\Emi;

use EE\Exception;
use Models\Base;

class Validator extends Base\Validator
{
    protected static $createRules = array(
        'bank_id'             => 'required|alpha_num|size:5',
        'emi_period'          => 'required|integer|min:1|max:12',
        'emi_interest'        => 'required|numeric',
        'method'              => 'sometimes|in:debit,credit,wallet,netbanking',
    );
}
