<?php

namespace RZP\Gateway\Netbanking\Axis\Mock;

use RZP\Base;
use RZP\Gateway\Netbanking\Axis\RequestFields;

class Validator extends Base\Validator
{
    protected static $authRules = [
        RequestFields::PAYEE_ID         => 'required|string',
        RequestFields::ENCRYPTED_STRING => 'required|string',
        RequestFields::RETURN_URL       => 'required|string'
    ];
}
