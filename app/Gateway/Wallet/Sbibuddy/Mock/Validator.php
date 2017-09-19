<?php

namespace RZP\Gateway\Wallet\Sbibuddy\Mock;

use RZP\Base;
use RZP\Gateway\Wallet\Sbibuddy\RequestFields;

class Validator extends Base\Validator
{
    protected static $authorizeRules = [
        RequestFields::EXTERNAL_TRANSACTION_ID  => 'required|string',
        RequestFields::ORDER_ID                 => 'required|string',
        RequestFields::AMOUNT                   => 'required|string',
        RequestFields::CURRENCY                 => 'required|string|in:INR',
        RequestFields::CALLBACK_URL             => 'required|url',
        RequestFields::BACK_URL                 => 'required|url',
        RequestFields::DESCRIPTION              => 'required|string',
        RequestFields::CATEGORY                 => 'sometimes|string',
        RequestFields::SUBCATEGORY              => 'sometimes|string',
        RequestFields::PROCESSOR_ID             => 'required|string|in:ALL',
    ];

    protected static $refundRules = [
        RequestFields::ORDER_ID             => 'required|string',
        RequestFields::AMOUNT               => 'required|string',
        RequestFields::TRANSACTION_ID       => 'required|string',
        RequestFields::REFUND_FEE           => 'required|integer|in:0,1',
        RequestFields::REFUND_REQUEST_ID    => 'required|string'
    ];

    // Either one of these should be present
    protected static $verifyRules = [
        RequestFields::ORDER_ID             => 'required_without_all:' . RequestFields::TRANSACTION_ID . '|string',
        RequestFields::TRANSACTION_ID       => 'required_without_all:' . RequestFields::ORDER_ID . '|string',
    ];
}
