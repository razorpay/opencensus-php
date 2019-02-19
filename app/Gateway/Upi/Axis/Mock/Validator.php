<?php

namespace RZP\Gateway\Upi\Axis\Mock;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $fetchTokenRules = [
        'merchId'       => 'required|alpha_num|size:9',
        'merchChanId'   => 'required|alpha_num|size:12',
        'unqTxnId'      => 'required|string|max:255',
        'unqCustId'     => 'required|string|max:255',
        'amount'        => ['required', 'regex:/^\d*(\.\d{2})$/'],
        'txnDtl'        => 'required|string|max:255',
        'currency'      => 'required|string|max:255',
        'orderId'       => 'required|string|max:255',
        'customerVpa'   => 'sometimes|max:255',
        'expiry'        => 'required|string|max:255',
        'sId'           => 'sometimes|string|max:255',
        'checkSum'      => 'required|string|max:10000',
        'accountNo'     => 'sometimes|alpha_num',
        'Ifsc'          => 'sometimes|string|size:4',
        'ifsc'          => 'sometimes|string|size:4',
    ];

    protected static $verifyRules = [
        'merchid'       => 'required|alpha_num',
        'merchchanid'   => 'required|alpha_num',
        'tranid'        => 'required|string|max:255',
        'mobilenumber'  => 'required|string|max:12',
        'checksum'      => 'required|string|max:10000',
    ];

    protected static $refundRules = [
        'merchId'           => 'required|alpha_num',
        'merchChanId'       => 'required|alpha_num',
        'txnRefundId'       => 'required|alpha_num|max:255',
        'mobNo'             => 'required|string|max:12',
        'txnRefundAmount'   => ['required', 'regex:/^\d*(\.\d{2})$/'],
        'unqTxnId'          => 'required|string|max:255',
        'refundReason'      => 'required|string',
        'sId'               => 'sometimes|string|max:255',
        'checkSum'          => 'required|string|max:10000',
    ];
}
