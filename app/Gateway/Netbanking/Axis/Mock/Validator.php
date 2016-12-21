<?php

namespace RZP\Gateway\Netbanking\Axis\Mock;

use RZP\Base;
use RZP\Gateway\Netbanking\Axis\RequestFields;

class Validator extends Base\Validator
{
    protected static $authRules = [
        RequestFields::PAYEE_ID                   => 'required|string',
        RequestFields::ENCRYPTED_STRING           => 'required|string',
        RequestFields::RETURN_URL                 => 'required|string'
    ];

    protected static $verifyRules = [
        RequestFields::PAYEE_ID                   => 'required|string',
        RequestFields::MERCHANT_UNIQUE_REFERENCE  => 'required|string|size:14',
        RequestFields::ITEM_CODE                  => 'required|string|size:14',
        RequestFields::PAYEE_ID                   => 'required|string',
        RequestFields::AMOUNT                     => 'required|numeric',
        RequestFields::DATE                       => 'required',
    ];
}
