<?php

namespace RZP\Gateway\Netbanking\Icici\Mock;

use RZP\Base;
use RZP\Gateway\Netbanking\Icici\RequestFields;

class Validator extends Base\Validator
{
    protected static $authRules = [
        RequestFields::MODE             => 'required|alpha|in:P',
        RequestFields::PAYEE_ID         => 'required|string',
        RequestFields::SPID             => 'required|string',
        RequestFields::ENCRYPTED_STRING => 'required|string',
    ];

    protected static $authDecryptedRules = [
        RequestFields::AMOUNT        => 'required',
        RequestFields::CONFIRMATION  => 'required|in:Y,N',
        RequestFields::CURRENCY_CODE => 'required',
        RequestFields::PAYMENT_ID    => 'required',
        RequestFields::ITEM_CODE     => 'required',
        RequestFields::RETURN_URL    => 'required',
        RequestFields::ACCOUNT_NO    => 'sometimes',
    ];

    protected static $verifyRules = [
        RequestFields::MODE              => 'required|alpha|size:1|in:V',
        RequestFields::PAYEE_ID          => 'required|string',
        RequestFields::AMOUNT            => 'required|numeric',
        RequestFields::PAYMENT_ID        => 'required|alpha_num|size:14',
        RequestFields::ITEM_CODE         => 'required|alpha_num|size:14',
        RequestFields::CURRENCY_CODE     => 'required|in:INR',
        RequestFields::ACCOUNT_NO        => 'sometimes|string',
        RequestFields::PAYMENT_DATE      => 'required|date_format:Y-m-d',
        RequestFields::RETURN_URL        => 'sometimes|string',
        RequestFields::SHOW_ON_SAME_PAGE => 'sometimes|string',
    ];
}
