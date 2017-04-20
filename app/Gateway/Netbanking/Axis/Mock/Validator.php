<?php

namespace RZP\Gateway\Netbanking\Axis\Mock;

use RZP\Base;
use RZP\Gateway\Netbanking\Axis\RequestFields;

class Validator extends Base\Validator
{
    protected static $authRules = [
        RequestFields::ENCRYPTED_STRING         => 'required|string',
        RequestFields::RETURN_URL               => 'required|string|url'
    ];

    protected static $verifyRules = [
        RequestFields::VERIFY_PAYEE_ID          => 'required|string',
        RequestFields::VERIFY_ITC               => 'required|string',
        RequestFields::VERIFY_PRN               => 'required|string|size:14',
        RequestFields::VERIFY_AMT               => 'required|numeric',
        RequestFields::VERIFY_DATE              => 'required|date_format:Y-m-d',
    ];
}
