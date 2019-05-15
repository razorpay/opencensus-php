<?php

namespace RZP\Gateway\Netbanking\Canara\Mock;

use RZP\Base;
use RZP\Gateway\Netbanking\Canara\RequestFields;

class Validator extends Base\Validator
{
    protected static $authRules = [
        RequestFields::CLIENT_CODE                    => 'required|alpha_num|max:40',
        RequestFields::MERCHANT_CODE                  => 'required|string',
        RequestFields::CURRENCY                       => 'required|in:INR',
        RequestFields::AMOUNT                         => 'required|numeric|max:999999999999999',
        RequestFields::SERVICE_CHARGE                 => 'required|in:0',
        RequestFields::PAYMENT_ID                     => 'required|alpha_num|size:14',
        RequestFields::SUCCESS_STATIC_FLAG            => 'required|in:N',
        RequestFields::FAILURE_STATIC_FLAG            => 'required|in:N',
        RequestFields::DATE                           => 'required|date_format:"d/m/Y\+H:i:s"',
        RequestFields::MODE_OF_TRANSACTION            => 'required|in:PER',
        RequestFields::CLIENT_ACCOUNT                 => 'sometimes',
        RequestFields::CHECKSUM                       => 'required',
        RequestFields::FLDREF1                        => 'required',
        RequestFields::FLDREF2                        => 'required',
        //additional fields - we do not send any value here
        'fldRef3'  => 'sometimes',
        'fldRef4'  => 'sometimes',
        'fldRef5'  => 'sometimes',
        'fldRef6'  => 'sometimes',
        'fldRef7'  => 'sometimes',
        'fldRef8'  => 'sometimes',
        'fldRef9'  => 'sometimes',
    ];

    protected static $verifyRules = [
        RequestFields::CLIENT_CODE                    => 'required|alpha_num|max:40',
        RequestFields::MERCHANT_CODE                  => 'required|string',
        RequestFields::CURRENCY                       => 'required|in:INR',
        RequestFields::AMOUNT                         => 'required|numeric|max:999999999999999',
        RequestFields::SERVICE_CHARGE                 => 'required|in:0',
        RequestFields::PAYMENT_ID                     => 'required|alpha_num|size:14',
        RequestFields::SUCCESS_STATIC_FLAG            => 'required|in:N',
        RequestFields::FAILURE_STATIC_FLAG            => 'required|in:N',
        RequestFields::VER_DATE                       => 'required|date_format:"d/m/Y H:i:s"',
        RequestFields::MODE_OF_TRANSACTION            => 'required|in:VRF',
        RequestFields::CLIENT_ACCOUNT                 => 'sometimes',
        RequestFields::PUR_DATE                       => 'required|date_format:"d/m/Y H:i:s"'
    ];
}
