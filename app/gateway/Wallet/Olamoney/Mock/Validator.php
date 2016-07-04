<?php

namespace Gateway\Wallet\Olamoney\Mock;

use Models\Base;

class Validator extends Base\Validator
{
    protected static $authorizeRules   = array(
        'paymentId'             => 'required|string',
        'bill'                  => 'required|regex:"^[a-zA-Z0-9_-]"',
        'phone'                 => 'required|regex:"^[789]\d{9}$"'
    );
}
