<?php

namespace RZP\Gateway\Netbanking\Pnb\Mock;

use RZP\Base;
use RZP\Gateway\Netbanking\Pnb\RequestFields;

class Validator extends Base\Validator
{
    protected static $authRules = [
        RequestFields::ENCDATA         => 'required|string',
    ];

    protected static $authorizeRules = [
        RequestFields::CHALLAN_NUMBER  => 'required|string|size:14',
        RequestFields::MERCHANT_DATE   => 'required|string',
        RequestFields::MERCHANT_AMOUNT => 'required|string',
        RequestFields::RETURN_URL      => 'required|url',
        RequestFields::ITEM_CODE       => 'required|string|size:14',
        RequestFields::CHECKSUM        => 'required|string',
        RequestFields::USER_NAME       => 'required|string',
        RequestFields::EMAIL           => 'required|email',
        RequestFields::REMARK          => 'required|string',
        RequestFields::PHONE_NUMBER    => 'required|string',
        RequestFields::ADDRESS         => 'required|string',
        RequestFields::ACCOUNT_NUMBER  => 'sometimes|string',
    ];

    protected static $verifyRules = [
        RequestFields::CHALLAN_NUMBER  => 'required|string|size:14',
        RequestFields::MERCHANT_DATE   => 'required|string',
        RequestFields::MERCHANT_AMOUNT => 'required|string',
        RequestFields::RETURN_URL      => 'required|url',
        RequestFields::ITEM_CODE       => 'required|string|size:14',
        RequestFields::CHECKSUM        => 'required|string',
    ];
}
