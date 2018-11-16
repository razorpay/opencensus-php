<?php

namespace RZP\Gateway\Netbanking\Idfc\Mock;

use RZP\Base;
use RZP\Gateway\Netbanking\Idfc\Fields;

class Validator extends Base\Validator
{
    protected static $authRules = [
        Fields::MERCHANT_ID             => 'required|string',
        Fields::PAYMENT_ID              => 'required|string|size:14',
        Fields::AMOUNT                  => 'required|string',
        Fields::RETURN_URL              => 'required',
        Fields::TRANSACTION_TYPE        => 'string|required',
        Fields::ACCOUNT_NUMBER          => 'sometimes|integer',
        Fields::PAYMENT_DESCRIPTION     => 'required|string',
        Fields::CHANNEL                 => 'required|string',
        Fields::MERCHANT_CODE           => 'required|string|size:4',
        Fields::TRANSACTION_CURRENCY    => 'required|string',
        Fields::CHECKSUM                => 'required|string',
    ];

    protected static $verifyRules = [
        Fields::MERCHANT_ID             => 'required|string',
        Fields::PAYMENT_ID              => 'required|string|size:14',
        Fields::AMOUNT                  => 'required|string',
        Fields::ACCOUNT_NUMBER          => 'sometimes|integer',
        Fields::TRANSACTION_TYPE        => 'string|required',
        Fields::BANK_REFERENCE_NUMBER   => 'required|string',
        Fields::MERCHANT_CODE           => 'required|string|size:4',
        Fields::CHECKSUM                => 'required|string',
    ];
}
