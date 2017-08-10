<?php

namespace RZP\Gateway\Wallet\Mpesa\Mock;

use RZP\Base;
use RZP\Gateway\Wallet\Mpesa\RequestFields;

class Validator extends Base\Validator
{
    protected static $authRules = [
        RequestFields::GATEWAY_PARAM => 'required|string',
        RequestFields::CHECKSUM      => 'required|string',
        'paymentId'                  => 'required|string',
    ];

    protected static $gatewayparamRules = [
        RequestFields::MERCHANT_CODE         => 'required|string',
        RequestFields::TRANSACTION_DATE      => 'required|string|date_format:dmY',
        RequestFields::TRANSACTION_REFERENCE => 'required|string|size:14',
        RequestFields::TRANSACTION_TYPE      => 'required|string|in:W',
        RequestFields::AMOUNT                => 'required|string',
        RequestFields::RETURN_URL            => 'required|string',
        RequestFields::NARRATION             => 'required|string|in:Razorpay',
        RequestFields::FILLER3               => 'sometimes|string',
    ];

    protected static $validateCustomerRules = [
        RequestFields::CHANNEL_ID    => 'required|numeric|in:11',
        RequestFields::REQUEST_ID    => 'required|string|size:13',
        RequestFields::MOBILE_NUMBER => 'required|string|size:10'
    ];

    protected static $pgSendOTPRules = [
        RequestFields::REQUEST_ID     => 'required|string|size:13',
        RequestFields::CHANNEL_ID     => 'required|numeric|in:11',
        RequestFields::ENTITY_TYPE_ID => 'required|numeric|in:80',
        RequestFields::MOBILE_NUMBER  => 'required|string|size:10'
    ];

    protected static $pgMrchntPymtRules = [
        RequestFields::MERCHANT_CODE         => 'required|string',
        RequestFields::TRANSACTION_DATE      => 'required|string|date_format:dmY',
        RequestFields::TRANSACTION_REFERENCE => 'required|string|size:14',
        RequestFields::TRANSACTION_TYPE      => 'required|string|in:W',
        RequestFields::AMOUNT                => 'required|numeric',
        RequestFields::MOBILE_NUMBER         => 'required|string|size:10',
        RequestFields::FROM_ENTITY_TYPE      => 'required|numeric|in:80',
        RequestFields::TO_ENTITY_TYPE        => 'required|numeric|in:85',
        RequestFields::COMMAND_ID            => 'required|string|in:O',
        RequestFields::OTP                   => 'required|string|size:4',
        RequestFields::OTP_REF_NUMBER        => 'required|string|size:21',
        RequestFields::CHANNEL_ID            => 'required|numeric|in:11',
    ];

    protected static $queryPaymentTransactionRules = [
        RequestFields::MERCHANT_CODE             => 'required|string',
        RequestFields::QUERY_TRANSACTION_DATE    => 'required|string|date_format:dmY',
        RequestFields::COM_TRANSACTION_ID        => 'sometimes|nullable|string|size:11',
        RequestFields::QUERY_TRANSACTION_REF     => 'required|string|size:14',
        RequestFields::PMT_TRANSACTION_REFERENCE => 'required|string|size:14',
        RequestFields::AMOUNT                    => 'required|numeric',
        RequestFields::CMDID                     => 'sometimes|numeric|in:111'
    ];

    protected static $refundPaymentTransactionRules = [
        RequestFields::MERCHANT_CODE         => 'required|string',
        RequestFields::COM_TRANSACTION_ID    => 'required|string|size:11',
        RequestFields::QUERY_TRANSACTION_REF => 'required|string|size:14',
        RequestFields::S2S_AMOUNT            => 'required|numeric',
        RequestFields::REFUND_NARRATION      => 'required|string|in:refund',
        RequestFields::REVERSAL_TYPE         => 'required|string|in:F,P'
    ];
}
