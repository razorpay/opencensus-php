<?php

namespace RZP\Gateway\Paytm\Mock;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $authRules = array(
        'REQUEST_TYPE'          => 'required|in:SEAMLESS,DEFAULT',
        'MID'                   => 'required|alpha_num',
        'ORDER_ID'              => 'required|size:14|alpha_num',
        'TXN_AMOUNT'            => 'required|numeric',
        'CUST_ID'               => 'required|email',
        'CHANNEL_ID'            => 'required|in:WEB,WAP',
        'INDUSTRY_TYPE_ID'      => 'required|alpha_num',
        'WEBSITE'               => 'required|',
        'CALLBACK_URL'          => 'required|url',
        'PAYMENT_MODE_ONLY'     => 'required_if:REQUEST_TYPE,SEAMLESS|in:Yes',
        'AUTH_MODE'             => 'required_with:PAYMENT_MODE_ONLY|in:3D,USRPWD',
        'PAYMENT_DETAILS'       => 'required_if:AUTH_MODE,3D',
        'PAYMENT_TYPE_ID'       => 'required_with:AUTH_MODE|in:DC,CC,NB',
        'CHECKSUMHASH'          => 'required|',
        'EMAIL'                 => 'sometimes|email',
        'MOBILE_NO'             => 'sometimes|numeric',
        'BANK_CODE'             => 'required_if:AUTH_MODE,USRPWD',
    );

    protected static $refundBodyRules = array(
        'mid'           => 'required',
        'refId'         => 'required|alpha_num',
        'txnId'         => 'required|',
        'orderId'       => 'required|size:14|alpha_num',
        'txnType'       => 'required|in:REFUND',
        'refundAmount'  => 'required|numeric',
    );

    protected static $refundHeadRules = array(
        'signature' => 'required'
    );

    protected static $verifyRefundBodyRules = array(
        'mid'           => 'required',
        'refId'         => 'required|alpha_num',
        'orderId'       => 'required|size:14|alpha_num',
    );

    protected static $verifyRefundHeadRules = array(
        'signature' => 'required'
    );
}
