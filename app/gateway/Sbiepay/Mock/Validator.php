<?php

namespace Gateway\Sbiepay\Mock;

use Models\Base;

class Validator extends Base\Validator
{
    protected static $authRules = array(
        'MerchantId'         => 'required',
        'OperatingMode'      => 'required|alpha_num',
        'MerchantCountry'    => 'required|in:IN',
        'MerchantCurrency'   => 'required|in:INR',
        'PostingAmount'      => 'required|numeric',
        'OtherDetails'       => 'sometimes',
        'SuccessURL'         => 'required|url',
        'FailURL'            => 'required|url',
        'AggregatorId'       => 'required',
        'MerchantOrderNo'    => 'required|size:14|alpha_num',
        'MerchantCustomerID' => 'required|email',
        'Paymode'            => 'required|in:NB,CC,DC,IMPS,WALLET',
        'Accesmedium'        => 'required|in:ONLINE',
        'TransactionSource'  => 'required|in:ONLINE'
    );

    protected static $verifyRules = array(
        'Atrn'            => 'required',
        'merchantId'      => 'required|alpha_num',
        'MerchantOrderNo' => 'required|alpha_num|size:14',
        'ReturnURL'       => 'required|url'
    );

    protected static $refundRules = array(
        'AggregatorId'      => 'required',
        'MerchantId'        => 'required|alpha_num',
        'RefundRequestID'   => 'required',
        'ATRN'              => 'required',
        'RefundAmount'      => 'required|numeric',
        'AmountCurrency'    => 'required|numeric',
        'MerchantOrderNo'   => 'required',
        'RefundResponseURL' => 'required|url',
    );
}
