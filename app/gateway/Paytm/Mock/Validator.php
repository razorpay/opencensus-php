<?php

namespace Gateway\Paytm\Mock;

use Models\Base;

class Validator extends Base\Validator
{
    protected static $authRules = array(
        'REQUEST_TYPE'          => 'required|in:SEAMLESS,DEFAULT',
        'MID'                   => 'required|alpha_num',
        'ORDER_ID'              => 'required|size:14|alpha_num',
        'TXN_AMOUNT'            => 'required|numeric',
        'CUST_ID'               => 'required|email',
        'CHANNEL_ID'            => 'required|in:WEB',
        'INDUSTRY_TYPE_ID'      => 'required|alpha',
        'WEBSITE'               => 'required|',
        'CALLBACK_URL'          => 'required|url',
        'PAYMENT_MODE_ONLY'     => 'required|in:Yes',
        'AUTH_MODE'             => 'required|in:3D,USRPWD',
        'PAYMENT_DETAILS'       => 'required|',
        'PAYMENT_TYPE_ID'       => 'required|in:DC,CC',
        'CHECKSUMHASH'          => 'required|',
        'EMAIL'                 => 'sometimes|email',
        'MOBILE_NO'             => 'sometimes|numeric',
    );
}
