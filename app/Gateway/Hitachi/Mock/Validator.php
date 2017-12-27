<?php

namespace RZP\Gateway\Hitachi\Mock;

use RZP\Base;
use RZP\Gateway\Hitachi\RequestFields;

class Validator extends Base\Validator
{
    protected static $authRules = [
        RequestFields::TRANSACTION_TYPE    => 'required|string',
        RequestFields::TRANSACTION_AMOUNT  => 'required|numeric',
        RequestFields::TRANSACTION_TIME    => 'required|string|date_format:His',
        RequestFields::TRANSACTION_DATE    => 'required|string|date_format:md',
        RequestFields::CARD_NUMBER         => 'required|numeric',
        RequestFields::EXPIRY_DATE         => 'required|numeric',
        RequestFields::CVV2                => 'required|numeric',
        RequestFields::MERCHANT_ID         => 'required|string',
        RequestFields::MERCHANT_REF_NUMBER => 'required|alpha_num|size:14',
        RequestFields::AUTH_STATUS         => 'sometimes|string',
        RequestFields::ECI                 => 'sometimes|numeric',
        RequestFields::XID                 => 'sometimes|string',
        RequestFields::ALGORITHM           => 'sometimes|numeric',
        RequestFields::CAVV2               => 'sometimes|string',
        RequestFields::UCAF                => 'sometimes|string',
    ];

    protected static $verifyRules = [
        RequestFields::TRANSACTION_TYPE    => 'required|in:TS',
        RequestFields::REQUEST_ID          => 'required|string',
        RequestFields::TRANSACTION_AMOUNT  => 'required|numeric',
        RequestFields::MERCHANT_ID         => 'required|string',
        RequestFields::TERMINAL_ID         => 'required|string',
        RequestFields::MERCHANT_REF_NUMBER => 'required|alpha_num|size:14',
    ];

    protected static $refundRules = [
        RequestFields::TRANSACTION_TYPE    => 'required|in:RF',
        RequestFields::REQUEST_ID          => 'required|string',
        RequestFields::TRANSACTION_AMOUNT  => 'required|numeric',
        RequestFields::TRANSACTION_TIME    => 'required|string|date_format:His',
        RequestFields::TRANSACTION_DATE    => 'required|string|date_format:mdY',
        RequestFields::RETRIEVAL_REF_NUM   => 'required|string|size:12',
        RequestFields::MERCHANT_ID         => 'required|string',
        RequestFields::TERMINAL_ID         => 'required|string',
        RequestFields::MERCHANT_REF_NUMBER => 'required|alpha_num|size:14',
    ];

    protected static $captureRules = [
        RequestFields::TRANSACTION_TYPE    => 'required|in:CP',
        RequestFields::REQUEST_ID          => 'required|string',
        RequestFields::TRANSACTION_AMOUNT  => 'required|numeric',
        RequestFields::TRANSACTION_TIME    => 'required|string|date_format:His',
        RequestFields::TRANSACTION_DATE    => 'required|string|date_format:md',
        RequestFields::RETRIEVAL_REF_NUM   => 'required|string|size:12',
        RequestFields::MERCHANT_ID         => 'required|string',
        RequestFields::MERCHANT_REF_NUMBER => 'required|alpha_num|size:14',
    ];

    protected static $reverseRules = [
        RequestFields::TRANSACTION_TYPE    => 'required|in:CN',
        RequestFields::TRANSACTION_AMOUNT  => 'required|numeric',
        RequestFields::TRANSACTION_TIME    => 'required|string|date_format:His',
        RequestFields::TRANSACTION_DATE    => 'required|string|date_format:md',
        RequestFields::RETRIEVAL_REF_NUM   => 'required|string|size:12',
        RequestFields::MERCHANT_ID         => 'required|string',
        RequestFields::MERCHANT_REF_NUMBER => 'required|alpha_num|size:14',
    ];
}
