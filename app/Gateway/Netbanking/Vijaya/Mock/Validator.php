<?php

namespace RZP\Gateway\Netbanking\Vijaya\Mock;

use RZP\Base;
use RZP\Gateway\Netbanking\Vijaya\RequestFields;

class Validator extends Base\Validator
{
    protected static $authRules = array(
        RequestFields::MERCHANT_CONSTANT => 'required|string|in:000000000010',
        RequestFields::AMOUNT            => 'required|numeric',
        RequestFields::MERCHANT_NAME     => 'required|string',
        RequestFields::MERCHANT_ID       => 'required|numeric',
        RequestFields::ITEM_CODE         => 'required|string|in:Razorpay',
        RequestFields::RETURN_URL        => 'required|url',
        RequestFields::PAYMENT_ID        => 'required|alpha_num|size:14',
        RequestFields::CURRENCY          => 'required|string|in:INR',

    );

    protected static $verifyRules = [
        RequestFields::BANK_REFERENCE_NUMBER => 'sometimes|string',
        RequestFields::MERCHANT_CONSTANT     => 'required|string|in:000000000010',
        RequestFields::AMOUNT                => 'required|numeric',
        RequestFields::MERCHANT_NAME         => 'required|string',
        RequestFields::MERCHANT_ID           => 'required|numeric',
        RequestFields::ITEM_CODE             => 'required|string|in:Razorpay',
        RequestFields::RETURN_URL            => 'required|url',
        RequestFields::PAYMENT_ID            => 'required|alpha_num|size:14',
        RequestFields::CURRENCY              => 'required|string|in:INR',
    ];
}
