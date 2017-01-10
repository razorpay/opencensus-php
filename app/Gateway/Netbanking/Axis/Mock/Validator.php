<?php

namespace RZP\Gateway\Netbanking\Axis\Mock;

use RZP\Base;
use RZP\Gateway\Netbanking\Axis\RequestFields;

class Validator extends Base\Validator
{
    protected static $authRules = [
        RequestFields::AUTHENTICATION_MENU_ID   => 'required|string|in:CIMSHP',
        RequestFields::AUTHENTICATION_CALL_MODE => 'required|string|in:2',
        RequestFields::CATEGORY_ID              => 'required|string|in:IRCSM',
        RequestFields::ENCRYPTED_STRING         => 'required|string',
        RequestFields::RETURN_URL               => 'required|string|url'
    ];

    protected static $verifyRules = [
        RequestFields::VERIFY_PAYEE_ID          => 'required|string',
        RequestFields::VERIFY_ITC               => 'required|string|size:14',
        RequestFields::VERIFY_PRN               => 'required|string|size:14',
        RequestFields::VERIFY_AMT               => 'required|numeric',
        RequestFields::VERIFY_DATE              => 'required|date_format:Y-m-d',
    ];
}
