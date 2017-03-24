<?php

namespace RZP\Gateway\Netbanking\Federal\Mock;

use RZP\Base;
use RZP\Gateway\Netbanking\Federal\RequestFields;

class Validator extends Base\Validator
{
    protected static $authRules = [
        RequestFields::ACTION       => 'required|string|in:Y',
        RequestFields::BANK_ID      => 'required|string|in:049',
        RequestFields::MODE         => 'required|string|in:P',
        RequestFields::PAYEE_ID     => 'required|string',
        RequestFields::PAYMENT_ID   => 'required|alpha_num|size:14',
        RequestFields::ITEM_CODE    => 'required|alpha_num|size:14',
        RequestFields::AMOUNT       => 'required',
        RequestFields::CURRENCY     => 'required|in:INR',
        RequestFields::LANGUAGE_ID  => 'required|in:001',
        RequestFields::STATE_FLAG   => 'required|in:H',
        RequestFields::USER_TYPE    => 'required|in:1',
        RequestFields::APP_TYPE     => 'required|string|in:corporate',
        RequestFields::CONFIRMATION => 'required|in:Y',
        RequestFields::RETURN_URL   => 'required|url',
    ];

    protected static $verifyRules = [
        RequestFields::ACTION          => 'sometimes|string|in:Y',
        RequestFields::BANK_ID         => 'sometimes|string|in:049',
        RequestFields::MODE            => 'sometimes|string|in:V',
        RequestFields::PAYEE_ID        => 'required|string',
        RequestFields::PAYMENT_ID      => 'required|alpha_num|size:14',
        RequestFields::ITEM_CODE       => 'required|alpha_num|size:14',
        RequestFields::AMOUNT          => 'required',
        RequestFields::CURRENCY        => 'sometimes|in:INR',
        RequestFields::LANGUAGE_ID     => 'sometimes|in:001',
        RequestFields::STATE_FLAG      => 'sometimes|in:H',
        RequestFields::USER_TYPE       => 'sometimes|in:1',
        RequestFields::APP_TYPE        => 'sometimes|string|in:corporate',
        RequestFields::CONFIRMATION    => 'sometimes|in:Y',
        RequestFields::BANK_PAYMENT_ID => 'sometimes|string', // for verify broken, BID is not needed
    ];
}
