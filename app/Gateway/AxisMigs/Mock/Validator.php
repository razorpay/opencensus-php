<?php

namespace RZP\Gateway\AxisMigs\Mock;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $authRules = [
        'vpc_Command'               => 'required|in:pay',
        'vpc_Amount'                => 'required|integer',
        'vpc_Currency'              => 'required|in:INR|max:3',
        'vpc_MerchTxnRef'           => 'required|alpha_num|size:14',
        'vpc_Version'               => 'required|in:1',
        'vpc_Locale'                => 'required|in:en',
        'vpc_CardNum'               => 'required|numeric|luhn|digits_between:12,19',
        'vpc_CardExp'               => 'required|size:4',
        'vpc_Merchant'              => 'required|alpha_num|max:16',
        'vpc_AccessCode'            => 'required|alpha_num|size:8',
        'vpc_SecureHash'            => 'required|alpha_num|size:64',
        'vpc_SecureHashType'        => 'required|in:SHA256',
        'vpc_User'                  => 'required|string',
        'vpc_Password'              => 'required|string',
        'vpc_Card'                  => 'required_if:vpc_VerType,3DS|in:MasterCard,Visa',
        'vpc_CardSecurityCode'      => 'required_if:vpc_VerType,3DS|numeric|digits_between:2,4',
        'vpc_3DSECI'                => 'required_if:vpc_VerType,3DS',
        'vpc_3DSXID'                => 'required_if:vpc_VerType,3DS',
        'vpc_3DSenrolled'           => 'required_if:vpc_VerType,3DS',
        'vpc_3DSstatus'             => 'required_if:vpc_VerType,3DS',
        'vpc_VerToken'              => 'required_if:vpc_VerType,3DS',
        'vpc_VerType'               => 'sometimes|in:3DS'
    ];

    protected static $authenticateRules = [
        'vpc_Command'               => 'required|in:pay',
        'vpc_Amount'                => 'required|integer',
        'vpc_Currency'              => 'required|in:INR|max:3',
        'vpc_MerchTxnRef'           => 'required|alpha_num|size:14',
        'vpc_Version'               => 'required|in:1',
        'vpc_ReturnURL'             => 'required|url',
        'vpc_Locale'                => 'required|in:en',
        'vpc_gateway'               => 'required|in:ssl,threeDSecure',
        'vpc_Card'                  => 'required|in:MasterCard,Visa,Amex',
        'vpc_CardNum'               => 'required|numeric|luhn|digits_between:12,19',
        'vpc_CardExp'               => 'required|size:4',
        'vpc_CardSecurityCode'      => 'required|numeric|digits_between:2,4',
        'vpc_Merchant'              => 'required|alpha_num|max:16',
        'vpc_AccessCode'            => 'required|alpha_num|size:8',
        'vpc_SecureHash'            => 'required|alpha_num|size:64',
        'vpc_SecureHashType'        => 'required|in:SHA256',
        'vpc_OrderInfo'             => 'sometimes|alpha_num|max:34',
    ];

    protected static $captureRules = [
        'vpc_Command'               => 'required|in:capture',
        'vpc_MerchTxnRef'           => 'required|alpha_num|size:14',
        'vpc_TransNo'               => 'required|',
        'vpc_Amount'                => 'required|integer',
        'vpc_Currency'              => 'required|in:INR|max:3',
        'vpc_Version'               => 'required|in:1',
        'vpc_Merchant'              => 'required|alpha_num|max:16',
        'vpc_AccessCode'            => 'required|alpha_num|size:8',
        'vpc_User'                  => 'required|',
        'vpc_Password'              => 'required|',
    ];

    protected static $refundRules = [
        'vpc_Command'               => 'required|in:refund',
        'vpc_MerchTxnRef'           => 'required|alpha_num|size:14',
        'vpc_TransNo'               => 'required|',
        'vpc_Amount'                => 'required|integer',
        'vpc_Currency'              => 'required|in:INR|max:3',
        'vpc_Version'               => 'required|in:1',
        'vpc_Merchant'              => 'required|alpha_num|max:16',
        'vpc_AccessCode'            => 'required|alpha_num|size:8',
        'vpc_User'                  => 'required|',
        'vpc_Password'              => 'required|',
    ];

    protected static $reverseRules = [
        'vpc_Command'               => 'required|in:voidAuthorisation',
        'vpc_Currency'              => 'required|in:INR',
        'vpc_MerchTxnRef'           => 'required|alpha_num|size:14',
        'vpc_TransNo'               => 'required|',
        'vpc_Version'               => 'required|in:1',
        'vpc_Merchant'              => 'required|alpha_num|max:16',
        'vpc_AccessCode'            => 'required|alpha_num|size:8',
        'vpc_User'                  => 'required|',
        'vpc_Password'              => 'required|',
    ];

    protected static $verifyRules = [
        'vpc_Command'               => 'required|in:queryDR',
        'vpc_MerchTxnRef'           => 'required|alpha_num|size:14',
        'vpc_Version'               => 'required|in:1',
        'vpc_Merchant'              => 'required|alpha_num|max:16',
        'vpc_AccessCode'            => 'required|alpha_num|size:8',
        'vpc_User'                  => 'required|',
        'vpc_Password'              => 'required|',
    ];
}
