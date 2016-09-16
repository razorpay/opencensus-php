<?php

namespace RZP\Gateway\Wallet\Olamoney\Mock;

use RZP\Models\Base;
use RZP\Gateway\Wallet\Olamoney;
use RZP\Gateway\Wallet\Olamoney\RequestFields;
use RZP\Gateway\Wallet\Olamoney\ResponseFields;

class Validator extends Base\Validator
{
    protected static $otpGenerateRules = array(
        RequestFields::PHONE    => 'required|string|size:10',
        RequestFields::EMAIL    => 'required|email'
    );

    protected static $otpSubmitRules = array(
        RequestFields::PHONE    => 'required|string|size:10',
        RequestFields::OTP      => 'required|string|size:6'
    );

    protected static $checkBalanceRules = array(
        RequestFields::USER_ACCESS_TOKEN    => 'required|string',
    );

    protected static $debitRules = array(
        RequestFields::ACCESS_TOKEN         => 'required|string',
        RequestFields::COMMAND              => 'required|in:debit',
        RequestFields::UNIQUE_ID            => 'required|string',
        RequestFields::AMOUNT               => 'required|numeric',
        RequestFields::UDF                  => 'required|string',
        RequestFields::CURRENCY             => 'required|in:INR',
        RequestFields::NOTIFICATION_URL     => 'required',
        RequestFields::RETURN_URL           => 'required',
        RequestFields::COMMENTS             => 'required|string',
        RequestFields::COUPON_CODE          => 'required|string',
        RequestFields::USER_ACCESS_TOKEN    => 'required|string',
        RequestFields::HASH                 => 'required|string',
    );

    protected static $refundRules = array(
        RequestFields::ACCESS_TOKEN     => 'required|string',
        RequestFields::COMMAND          => 'required|in:refund',
        RequestFields::UNIQUE_ID        => 'required|string',
        RequestFields::COMMENTS         => 'required|string',
        RequestFields::UDF              => 'required|string',
        RequestFields::HASH             => 'required|string',
        RequestFields::RETURN_URL       => 'sometimes',
        RequestFields::NOTIFICATION_URL => 'sometimes',
        RequestFields::AMOUNT           => 'required|numeric',
        RequestFields::BALANCE_TYPE     => 'required|string',
        RequestFields::BALANCE_NAME     => 'required|string',
        RequestFields::SALE_ID          => 'required|string',
        RequestFields::CURRENCY         => 'required|in:INR'
    );

    protected static $verifyRules = array(
        RequestFields::UNIQUE_BILL_ID   => 'required|string',
        RequestFields::ACCESS_TOKEN     => 'required|string',
        RequestFields::TIMESTAMP        => 'required|date_format:Y-m-d H:i:s',
        RequestFields::HASH             => 'required|string',
    );
}
