<?php

namespace RZP\Gateway\Netbanking\Oriental\Mock;

use RZP\Base;
use RZP\Gateway\Netbanking\Oriental\RequestFields;

final class Validator extends Base\Validator
{
    /**
     * TODO: Check this once
     */

    protected static $authRules = [
        RequestFields::RETURN_URL   => 'required|string|url',
        RequestFields::CATEGORY_ID  => 'required|string|in:400',
        RequestFields::QUERY_STRING => 'required|string'
    ];

    // TODO: Validate Query String

    protected static $verifyRules = [
        RequestFields::PAYEE_ID    => 'required|string',
        RequestFields::PAY_REF_NUM => 'required|string|size:14',
        RequestFields::ITEM_CODE   => 'required|string|size:14',
        RequestFields::AMOUNT      => 'required',
        RequestFields::CRN         => 'required|string|in:INR',
        RequestFields::RETURN_URL  => 'required|string',
        RequestFields::BID         => 'required|string'
    ];
}
