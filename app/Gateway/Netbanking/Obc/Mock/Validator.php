<?php

namespace RZP\Gateway\Netbanking\Obc\Mock;

use RZP\Base;
use RZP\Gateway\Netbanking\Obc\RequestFields;

final class Validator extends Base\Validator
{
    protected static $authRules = [
        RequestFields::RETURN_URL   => 'required|string|url',
        RequestFields::CATEGORY_ID  => 'required|string|in:400',
        RequestFields::QUERY_STRING => 'required|string'
    ];

    /**
     * We validate the authorize QS parameter
     * @var array
     */
    protected static $authorizeQsRules = [
        RequestFields::TRAN_CRN    => 'required|in:INR',
        RequestFields::TXN_AMOUNT  => 'required|integer',
        RequestFields::PAYEE_ID    => 'required|string',
        RequestFields::PAY_REF_NUM => 'required|string|size:14',
        RequestFields::ITEM_CODE   => 'required|string|size:23'
    ];

    protected static $verifyRules = [
        RequestFields::PAYEE_ID    => 'required|string',
        RequestFields::PAY_REF_NUM => 'required|string|size:14',
        RequestFields::ITEM_CODE   => 'required|string|size:23  ',
        RequestFields::AMOUNT      => 'required|string',
        RequestFields::RETURN_URL  => 'required|string|url',
        RequestFields::BID         => 'required|string|in:9999999999'
    ];
}
