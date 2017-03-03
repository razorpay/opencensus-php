<?php

namespace RZP\Gateway\Netbanking\Federal\Mock;

use RZP\Base;
use RZP\Gateway\Netbanking\Federal\RequestFields;

class Validator extends Base\Validator
{
    protected static $authRules = [
        RequestFields::ACTION       => 'required',
        RequestFields::BANK_ID      => 'required',
        RequestFields::MODE         => 'required',
        RequestFields::PAYEE_ID     => 'required',
        RequestFields::PAYMENT_ID   => 'required',
        RequestFields::ITEM_CODE    => 'required',
        RequestFields::AMOUNT       => 'required',
        RequestFields::CURRENCY     => 'required',
        RequestFields::LANGUAGE_ID  => 'required',
        RequestFields::STATE_FLAG   => 'required',
        RequestFields::USER_TYPE    => 'required',
        RequestFields::APP_TYPE     => 'required',
        RequestFields::CONFIRMATION => 'required',
        RequestFields::RETURN_URL   => 'required',
    ];

    protected static $verifyRules = [
        RequestFields::ACTION          => 'required',
        RequestFields::BANK_ID         => 'required',
        RequestFields::MODE            => 'required',
        RequestFields::PAYEE_ID        => 'required',
        RequestFields::PAYMENT_ID      => 'required',
        RequestFields::ITEM_CODE       => 'required',
        RequestFields::AMOUNT          => 'required',
        RequestFields::CURRENCY        => 'required',
        RequestFields::LANGUAGE_ID     => 'required',
        RequestFields::STATE_FLAG      => 'required',
        RequestFields::USER_TYPE       => 'required',
        RequestFields::APP_TYPE        => 'required',
        RequestFields::CONFIRMATION    => 'required',
        RequestFields::BANK_PAYMENT_ID => 'sometimes', // for verify broken, BID is not needed
    ];
}
