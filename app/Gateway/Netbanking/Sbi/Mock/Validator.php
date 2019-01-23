<?php

namespace RZP\Gateway\Netbanking\Sbi\Mock;

use RZP\Base;
use RZP\Gateway\Netbanking\Sbi\RequestFields;

class Validator extends Base\Validator
{
    protected static $authRules = [
        RequestFields::REF_NO       => 'required|string',
        RequestFields::AMOUNT       => 'required|numeric',
        RequestFields::PAYMENT_ID   => 'required|string',
        RequestFields::REDIRECT_URL => 'required|url',
        RequestFields::CANCEL_URL   => 'required|url',
        RequestFields::CHECKSUM     => 'required|string',
    ];

    protected static $verifyRules = [
        RequestFields::REF_NO       => 'required|string',
        RequestFields::BANK_REF_NO  => 'sometimes|string',
        RequestFields::AMOUNT       => 'required|numeric',
        RequestFields::CHECKSUM     => 'required|string',
    ];
}
