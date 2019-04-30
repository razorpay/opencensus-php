<?php

namespace RZP\Gateway\Netbanking\Federal\Mock;

use RZP\Base;
use RZP\Gateway\Netbanking\Federal\RequestFields;

class Validator extends Base\Validator
{
    protected static $authRules = [
        RequestFields::ACTION       => 'sometimes|string|in:Y',
        RequestFields::BANK_ID      => 'required|string|in:049',
        RequestFields::MODE         => 'required|string|in:P',
        RequestFields::PAYEE_ID     => 'required|string',
        RequestFields::PAYMENT_ID   => 'required|string|between:14,29',
        RequestFields::ITEM_CODE    => 'required|alpha_num',
        RequestFields::AMOUNT       => 'required',
        RequestFields::CURRENCY     => 'required|in:INR',
        RequestFields::LANGUAGE_ID  => 'required|in:001',
        RequestFields::STATE_FLAG   => 'required|in:H',
        RequestFields::USER_TYPE    => 'required|in:1',
        RequestFields::APP_TYPE     => 'required|string|in:corporate',
        RequestFields::CONFIRMATION => 'required|in:Y',
        RequestFields::RETURN_URL   => 'required|url',
        RequestFields::HASH         => 'required|string',
    ];

    protected static $verifyRules = [
        RequestFields::PAYEE_ID        => 'required|string',
        RequestFields::PAYMENT_ID      => 'required|string|max:29',
        RequestFields::ITEM_CODE       => 'required|alpha_num|size:14',
        RequestFields::AMOUNT          => 'required',
    ];
}
