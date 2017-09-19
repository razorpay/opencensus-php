<?php

namespace RZP\Gateway\Netbanking\Indusind\Mock;

use RZP\Base;
use RZP\Gateway\Netbanking\Indusind\RequestFields;

class Validator extends Base\Validator
{
    protected static $authRules = [
        RequestFields::MODE             => 'required|alpha|in:P',
        RequestFields::PAYEE_ID         => 'required|string',
        RequestFields::USER_TYPE        => 'required|string',
        RequestFields::ENCRYPTED_STRING => 'required|string',
    ];

    protected static $authDecryptedRules = [
        RequestFields::AMOUNT              => 'required',
        RequestFields::MERCHANT_REFERENCE  => 'required',
        RequestFields::CURRENCY_CODE       => 'required|in:INR',
        RequestFields::RETURN_URL          => 'required',
        RequestFields::ITEM_CODE           => 'required',
        RequestFields::CONFIRMATION        => 'required|in:Y,N',
        RequestFields::ACCOUNT_NUMBER      => 'sometimes',
    ];

    protected static $verifyRules = [
        RequestFields::MODE             => 'required|alpha|in:V',
        RequestFields::PAYEE_ID         => 'required|string',
        RequestFields::USER_TYPE        => 'required|string',
        RequestFields::ENCRYPTED_STRING => 'required|string',
    ];
}
