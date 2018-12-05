<?php

namespace RZP\Gateway\AxisGenius\Mock;

use RZP\Gateway\AxisMigs;

class Validator extends AxisMigs\Mock\Validator
{
    protected static $authenticateRules = array(
        'vpc_Command'               => 'required|in:pay',
        'vpc_Amount'                => 'required|integer',
        'vpc_Currency'              => 'required|in:INR|max:3',
        'vpc_MerchTxnRef'           => 'required|alpha_num|size:14',
        'vpc_Version'               => 'required|in:1',
        'vpc_ReturnURL'             => 'required|url',
        'vpc_Locale'                => 'required|in:en',
        'vpc_gateway'               => 'required|in:ssl,threeDSecure',
        'vpc_Card'                  => 'required|in:MasterCard,Visa',
        'vpc_CardNum'               => 'required|numeric|luhn|digits_between:12,19',
        'vpc_CardExp'               => 'required|size:4',
        'vpc_CardSecurityCode'      => 'required|numeric|digits_between:2,4',
        'vpc_MerchantId'            => 'required|alpha_num|max:16',
        'vpc_AccessCode'            => 'required|alpha_num|size:8',
        'vpc_SecureHash'            => 'required|alpha_num|size:64',
        'vpc_SecureHashType'        => 'required|in:SHA256',
        'vpc_OrderInfo'             => 'sometimes|alpha_num|max:34',
    );
}
