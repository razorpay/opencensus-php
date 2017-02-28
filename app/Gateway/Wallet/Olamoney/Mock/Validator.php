<?php

namespace RZP\Gateway\Wallet\Olamoney\Mock;

use RZP\Base;
use RZP\Gateway\Wallet\Olamoney;
use RZP\Gateway\Wallet\Olamoney\RequestFields;

class Validator extends Base\Validator
{
    protected static $creditRules   = [
        'paymentId'                                                         => 'required|string',
        RequestFields::BILL                                                 => 'required|array',
        RequestFields::BILL . '.' . RequestFields::MERCHANT_REFERENCE_ID    => 'required|alpha_num',
        RequestFields::BILL . '.' . RequestFields::COMMAND                  => 'required|string|in:credit',
        RequestFields::BILL . '.' . RequestFields::RETURN_URL               => 'required|url',
        RequestFields::BILL . '.' . RequestFields::NOTIFICATION_URL         => 'required',
        RequestFields::BILL . '.' . RequestFields::USER_ACCESS_TOKEN        => 'required|string',
        RequestFields::BILL . '.' . RequestFields::CURRENCY                 => 'required|string|in:INR',
        RequestFields::BILL . '.' . RequestFields::BALANCE_TYPE             => 'required|string|in:cash',
        RequestFields::BILL . '.' . RequestFields::BALANCE_NAME             => 'required|string|in:cash',
        RequestFields::BILL . '.' . RequestFields::AMOUNT                   => 'required|numeric',
        RequestFields::BILL . '.' . RequestFields::COMMENTS                 => 'sometimes|string',
        RequestFields::BILL . '.' . RequestFields::UDF                      => 'required|string',
        RequestFields::PHONE                                                => 'sometimes|string|size:10',
    ];

    protected static $otpGenerateRules = [
        RequestFields::PHONE    => 'required|string|size:10',
        RequestFields::EMAIL    => 'required|email'
    ];

    protected static $otpSubmitRules = [
        RequestFields::PHONE    => 'required|string|size:10',
        RequestFields::OTP      => 'required|string|size:6'
    ];

    protected static $checkBalanceRules = [
        RequestFields::USER_ACCESS_TOKEN    => 'required|string',
    ];

    protected static $debitRules = [
        RequestFields::ACCESS_TOKEN         => 'required|string',
        RequestFields::COMMAND              => 'required|in:debit',
        RequestFields::UNIQUE_ID            => 'required|string',
        RequestFields::AMOUNT               => 'required|numeric',
        RequestFields::UDF                  => 'required|string',
        RequestFields::CURRENCY             => 'required|in:INR',
        RequestFields::NOTIFICATION_URL     => 'required|url',
        RequestFields::RETURN_URL           => 'required',
        RequestFields::COMMENTS             => 'required|string',
        RequestFields::COUPON_CODE          => 'required|string',
        RequestFields::USER_ACCESS_TOKEN    => 'required|string',
        RequestFields::HASH                 => 'required|regex:"^[a-f0-9]+$"',
    ];

    protected static $refundRules = [
        RequestFields::ACCESS_TOKEN     => 'required|string',
        RequestFields::COMMAND          => 'required|in:refund',
        RequestFields::UNIQUE_ID        => 'required|string',
        RequestFields::COMMENTS         => 'required|string',
        RequestFields::UDF              => 'required|string',
        RequestFields::HASH             => 'required|regex:"^[a-f0-9]+$"',
        RequestFields::RETURN_URL       => 'sometimes',
        RequestFields::NOTIFICATION_URL => 'sometimes',
        RequestFields::AMOUNT           => 'required|numeric',
        RequestFields::BALANCE_TYPE     => 'required|string',
        RequestFields::BALANCE_NAME     => 'required|string',
        RequestFields::SALE_ID          => 'required|string',
        RequestFields::CURRENCY         => 'required|in:INR'
    ];

    protected static $verifyRules = [
        RequestFields::UNIQUE_BILL_ID   => 'required|string',
        RequestFields::ACCESS_TOKEN     => 'required|string',
        RequestFields::TIMESTAMP        => 'required|date_format:Y-m-d H:i:s',
        RequestFields::HASH             => 'required|regex:"^[a-f0-9]+$"',
    ];
}
