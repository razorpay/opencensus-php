<?php

namespace RZP\Gateway\Wallet\Mpesa\Mock;

use RZP\Base;
use RZP\Gateway\Wallet\Mpesa\SoapAction;
use RZP\Gateway\Wallet\Mpesa\RequestFields;

class Validator extends Base\Validator
{
    protected static $authRules = [
        RequestFields::GATEWAY_PARAM => 'required|string',
        RequestFields::CHECKSUM      => 'required|string',
    ];

    protected static $gatewayparamRules = [
        RequestFields::MERCHANT_CODE         => 'required|string',
        RequestFields::TRANSACTION_DATE      => 'required|string|date_format:dmY',
        RequestFields::TRANSACTION_REFERENCE => 'required|string|size:14',
        RequestFields::TRANSACTION_TYPE      => 'required|string|in:W',
        RequestFields::AMOUNT                => 'required|string',
        RequestFields::NARRATION             => 'required|string|in:Razorpay Payments',
        RequestFields::RETURN_URL            => 'required|string',
        RequestFields::SURCHARGE             => 'required|string|in:0.0',
    ];

    // protected static $validateCustomerRules = [
    //     SoapAction::CUSTOMER_API => 'required|array|size:1'
    // ];
}
