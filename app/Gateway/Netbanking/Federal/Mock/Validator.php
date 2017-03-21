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
        RequestFields::ACTION          => 'sometimes',
        RequestFields::BANK_ID         => 'sometimes',
        RequestFields::MODE            => 'sometimes',
        RequestFields::PAYEE_ID        => 'required',
        RequestFields::PAYMENT_ID      => 'required',
        RequestFields::ITEM_CODE       => 'required',
        RequestFields::AMOUNT          => 'required',
        RequestFields::CURRENCY        => 'sometimes',
        RequestFields::LANGUAGE_ID     => 'sometimes',
        RequestFields::STATE_FLAG      => 'sometimes',
        RequestFields::USER_TYPE       => 'sometimes',
        RequestFields::APP_TYPE        => 'sometimes',
        RequestFields::CONFIRMATION    => 'sometimes',
        RequestFields::BANK_PAYMENT_ID => 'sometimes', // for verify broken, BID is not needed
    ];
}
