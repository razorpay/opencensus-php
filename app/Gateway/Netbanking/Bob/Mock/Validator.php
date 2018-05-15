<?php

namespace RZP\Gateway\Netbanking\Bob\Mock;

use RZP\Base;
use RZP\Gateway\Netbanking\Bob\RequestFields;

class Validator extends Base\Validator
{
    protected static $authRules = array(
        RequestFields::BANK_ID          => 'required|string',
        RequestFields::BANK_FIXED_VALUE => 'required|alpha_num',
        RequestFields::BILLER_NAME      => 'required|string',
        RequestFields::AMOUNT           => 'required|numeric',
        RequestFields::CALLBACK_URL     => 'required|url',
        RequestFields::PAYMENT_ID       => 'required|alpha_num|size:14'
    );

    protected static $verifyRules = [
        RequestFields::PAYMENT_ID => 'required|alpha_num|size:14'
    ];
}
