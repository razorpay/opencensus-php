<?php

namespace RZP\Gateway\Netbanking\Allahabad\Mock;

use RZP\Base;
use RZP\Gateway\Netbanking\Allahabad\RequestFields;

class Validator extends Base\Validator
{
    protected static $authRules = [
        RequestFields::ACTION                 => 'required|string|in:Y',
        RequestFields::BANK_ID                => 'required|string|in:ALB',
        RequestFields::MODE_OF_PAYMENT        => 'required|string|in:P',
        RequestFields::PAYEE_ID               => 'required|string|in:RAZOR',
        RequestFields::ITEM_CODE              => 'required|alpha_num',
        RequestFields::PRODUCT_REF_NUMBER     => 'required|string|between:14,29',
        RequestFields::AMOUNT                 => 'required',
        RequestFields::CURRENCY               => 'required|in:INR',
        RequestFields::RETURN_URL             => 'required|url',
        RequestFields::CG                     => 'required|string|in:Y',
        RequestFields::LANGUAGE_ID            => 'required|in:001',
        RequestFields::USER_TYPE              => 'required|in:1',
        RequestFields::APP_TYPE               => 'required|string|in:retail',
        RequestFields::MERCHANT_CODE          => 'required'
    ];

    protected static $verifyRules = [
        RequestFields::ACTION                 => 'required|string|in:Y',
        RequestFields::BANK_ID                => 'required|string|in:ALB',
        RequestFields::MODE_OF_PAYMENT        => 'required|string|in:V',
        RequestFields::PAYEE_ID               => 'required|string|in:RAZOR',
        RequestFields::ITEM_CODE              => 'required|alpha_num',
        RequestFields::PRODUCT_REF_NUMBER     => 'required|string|between:14,29',
        RequestFields::AMOUNT                 => 'required',
        RequestFields::CURRENCY               => 'required|in:INR',
        RequestFields::LANGUAGE_ID            => 'required|in:001',
        RequestFields::USER_TYPE              => 'required|in:1',
        RequestFields::APP_TYPE               => 'required|string|in:retail',
        RequestFields::STATFLG                => 'required|string|in:H',
        RequestFields::BANK_TRANSACTION_ID    => 'sometimes'
    ];
}
