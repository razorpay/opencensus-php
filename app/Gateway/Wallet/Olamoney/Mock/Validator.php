<?php

namespace RZP\Gateway\Wallet\Olamoney\Mock;

use RZP\Models\Base;
use RZP\Gateway\Wallet\Olamoney;
use RZP\Gateway\Wallet\Olamoney\RequestFields;
use RZP\Gateway\Wallet\Olamoney\ResponseFields;

class Validator extends Base\Validator
{
    protected static $debitRules   = array(
        'paymentId'                                            => 'required|alpha_num',
        RequestFields::BILL                                    => 'required|array',
        RequestFields::BILL . '.' . RequestFields::UNIQUE_ID   => 'required|alpha_num',
        RequestFields::BILL . '.' . RequestFields::AMOUNT      => 'required|numeric',
        RequestFields::BILL . '.' . RequestFields::COMMENTS    => 'sometimes|string',
        RequestFields::BILL . '.' . RequestFields::UDF         => 'required|string',
        RequestFields::PHONE                                   => 'required|integer'
    );

    protected static $refundRules = array(
        RequestFields::ACCESS_TOKEN     => 'required|string',
        RequestFields::COMMAND          => 'required|in:refund',
        RequestFields::UNIQUE_ID        => 'required|string',
        RequestFields::COMMENTS         => 'required|string',
        RequestFields::UDF              => 'required|string',
        RequestFields::HASH             => 'required|string',
        RequestFields::RETURN_URL       => 'sometimes',
        RequestFields::NOTIFICATION_URL => 'sometimes',
        RequestFields::AMOUNT           => 'required|numeric',
        RequestFields::BALANCE_TYPE     => 'required|string',
        RequestFields::BALANCE_NAME     => 'required|string',
        RequestFields::SALE_ID          => 'required|string',
        RequestFields::CURRENCY         => 'required|in:INR'
    );

    protected static $verifyRules = array(
        RequestFields::UNIQUE_BILL_ID   => 'required|string',
        RequestFields::ACCESS_TOKEN     => 'required|string',
        RequestFields::TIMESTAMP        => 'required|date_format:Y-m-d H:i:s',
        RequestFields::HASH             => 'required|string',
    );
}
