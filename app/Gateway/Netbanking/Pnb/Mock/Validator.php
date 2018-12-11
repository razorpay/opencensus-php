<?php

namespace RZP\Gateway\Netbanking\Pnb\Mock;

use RZP\Base;
use RZP\Gateway\Netbanking\Pnb\RequestFields;

class Validator extends Base\Validator
{
    protected static $authRules = [
        RequestFields::API_KEY        => 'required|string',
        RequestFields::ENCRYPTED_DATA => 'required|string',
    ];

    protected static $authorizeRules = [
        RequestFields::API_KEY        => 'required|string',
        RequestFields::PAYMENT_ID     => 'required|string|size:14',
        RequestFields::MODE           => 'required|string|in:LIVE,TEST',
        RequestFields::AMOUNT         => 'required|numeric',
        RequestFields::RETURN_URL     => 'required|url',
        RequestFields::CURRENCY       => 'required|string|in:INR',
        RequestFields::CHECKSUM       => 'required|string',
        RequestFields::DESCRIPTION    => 'required|string',
        RequestFields::EMAIL          => 'required|email',
        RequestFields::NAME           => 'required|string',
        RequestFields::PHONE          => 'required|string',
        RequestFields::ADDRESS_LINE_1 => 'sometimes|string',
        RequestFields::ADDRESS_LINE_2 => 'sometimes|string',
        RequestFields::CITY           => 'required|string',
        RequestFields::STATE          => 'sometimes|string',
        RequestFields::COUNTRY        => 'required|string',
        RequestFields::ZIP_CODE       => 'required|string',
        RequestFields::BANK_CODE      => 'required|string|in:PNBN,PNBM',
    ];

    protected static $verifyRules = [
        RequestFields::API_KEY         => 'required|string',
        RequestFields::PAYMENT_ID      => 'sometimes|string|size:14',
        RequestFields::CHECKSUM        => 'required|string',
        RequestFields::BANK_CODE       => 'sometimes|string|in:PNBN,PNBM',
        RequestFields::BANK_PAYMENT_ID => 'sometimes|string',
        RequestFields::RESPONSE_CODE   => 'sometimes|numeric'
    ];

    protected static $refundRules = [
        RequestFields::API_KEY         => 'required|string',
        RequestFields::BANK_PAYMENT_ID => 'sometimes|string',
        RequestFields::AMOUNT          => 'required|numeric',
        RequestFields::DESCRIPTION     => 'required|string',
        RequestFields::CHECKSUM        => 'required|string',
    ];
}
