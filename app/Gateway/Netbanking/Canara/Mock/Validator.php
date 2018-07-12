<?php

namespace RZP\Gateway\Netbanking\Canara\Mock;

use RZP\Base;
use RZP\Gateway\Netbanking\Canara\RequestFields;

class Validator extends Base\Validator
{
    protected static $authRules = [
        RequestFields::CLIENT_CODE                    => 'required|alpha_num|max:40',
        RequestFields::MERCHANT_CODE                  => 'required|string|max:100',
        RequestFields::CURRENCY                       => 'required|in:INR',
        RequestFields::AMOUNT                         => 'required|numeric|max:999999999999999',
        RequestFields::SERVICE_CHARGE                 => 'required|in:0',
        RequestFields::PAYMENT_ID                     => 'required|alpha_num|size:14',
        RequestFields::SUCCESS_STATIC_FLAG            => 'required|in:N',
        RequestFields::FAILURE_STATIC_FLAG            => 'required|in:N',
        RequestFields::DATE                           => 'required|', // have to modify
        RequestFields::MODE_OF_TRANSACTION            => 'required|in:PUR',
        RequestFields::CLIENT_ACCOUNT                 => 'sometimes',
        'DynamicUrl'=>'required',
    ];

}
