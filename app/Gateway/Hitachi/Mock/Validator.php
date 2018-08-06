<?php

namespace RZP\Gateway\Hitachi\Mock;

use RZP\Base;
use RZP\Gateway\Hitachi\RequestFields;
use RZP\Gateway\Hitachi\TerminalFields;

class Validator extends Base\Validator
{
    protected static $authRules = [
        RequestFields::TRANSACTION_TYPE    => 'required|in:00,SI|string',
        RequestFields::TRANSACTION_AMOUNT  => 'required|numeric',
        RequestFields::TRANSACTION_TIME    => 'required|string|date_format:His',
        RequestFields::TRANSACTION_DATE    => 'required|string|date_format:md',
        RequestFields::CARD_NUMBER         => 'required|numeric',
        RequestFields::EXPIRY_DATE         => 'required|numeric',
        RequestFields::CVV2                => 'required_if:transaction_type,00|numeric',
        RequestFields::MERCHANT_ID         => 'required|string',
        RequestFields::MERCHANT_REF_NUMBER => 'required|alpha_num|size:14',
        RequestFields::AUTH_STATUS         => 'sometimes|string',
        RequestFields::ECI                 => 'sometimes|numeric',
        RequestFields::XID                 => 'sometimes|string',
        RequestFields::ALGORITHM           => 'sometimes|numeric',
        RequestFields::CAVV2               => 'sometimes|string',
        RequestFields::UCAF                => 'sometimes|string',
        RequestFields::CURRENCY_CODE       => 'sometimes|string',
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
        RequestFields::TRANSACTION_DATE    => 'required|string|date_format:dmY',
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

    protected static $merchantOnboardRules = [
        TerminalFields::S_NO                 => 'required|alpha_num|max:10',
        TerminalFields::ACTION_CODE          => 'required|string|size:1',
        TerminalFields::EXISTING_MERCHANT    => 'required|string|size:1',
        TerminalFields::CUSTOMER_NO          => 'sometimes|string|max:15',
        TerminalFields::SUPER_MID            => 'required|string|size:15',
        TerminalFields::MID                  => 'required|alpha_num|size:15',
        TerminalFields::TID                  => 'required|alpha_num|size:8',
        TerminalFields::MERCHANT_DB_NAME     => 'required|string|max:25',
        TerminalFields::MERCHANT_GROUP       => 'sometimes|string|max:8',
        TerminalFields::BANK                 => 'required|string|max:25',
        TerminalFields::LOCATION             => 'required|string|max:23',
        TerminalFields::STATE                => 'required|string|size:2',
        TerminalFields::ZIPCODE              => 'required|numeric|digits:6',
        TerminalFields::COUNTRY              => 'required|string|size:2',
        TerminalFields::MCC                  => 'required|numeric|digits:4',
        TerminalFields::TERMINAL_ACTIVE      => 'required|string|size:1',
        TerminalFields::MERCHANT_STATUS      => 'required|string|size:1',
        TerminalFields::INTERNATIONAL        => 'required|string|size:1',
        TerminalFields::PROCESS_ID           => 'required|string|max:50',
        TerminalFields::TRANS_MODE           => 'required|string',
        TerminalFields::CURRENCY             => 'required|string',
        TerminalFields::SPONSOR_BANK         => 'sometimes|string',
        TerminalFields::CITY                 => 'sometimes|string|max:13',
        TerminalFields::MERCHANT_NAME        => 'sometimes|string',
    ];
}
