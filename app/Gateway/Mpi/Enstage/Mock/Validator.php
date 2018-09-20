<?php

namespace RZP\Gateway\Mpi\Enstage\Mock;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $otpGenerateRules = [
        'version'                     => 'required|string|in:v.0.0',
        'merchantTxnId'               => 'required|string|max:30',
        'messageHash'                 => 'required|string',
        'cardDetails'                 => 'required|string',
        'txnDetails'                  => 'required|array',
        'txnDetails.expPayIdentifier' => 'required|string',
        'txnDetails.merchantName'     => 'required|string|max:100',
        'txnDetails.merchantId'       => 'required|string|max:50',
        'txnDetails.amount'           => 'required|numeric',
        'txnDetails.currency'         => 'required|numeric|in:356',
        'txnDetails.currencyexponent' => 'required|numeric|in:2',
        'txnDetails.orderDescription' => 'sometimes|string',
        'txnDetails.deviceCategoryId' => 'required|string|in:1,2,3',
        'txnDetails.acquirerBin'      => 'required|numeric|min:100000|max:999999',
        'additionaldataReq'           => 'sometimes|array',
        'additionaldataReq.deviceid'  => 'sometimes|string',
        'additionaldataReq.userAgent' => 'required|string',
    ];

    protected static $otpSubmitRules = [
        'version'                     => 'required|string|in:v.0.0',
        'merchantTxnId'               => 'required|string|max:30',
        'acsTxnId'                    => 'required|string|max:30',
        'otpToken'                    => 'required|string|max:30',
        'messageHash'                 => 'required|string',
    ];

    protected static $cardDetailsRules = [
        'cardNumber'                  => 'required|string',
         'expiry'                     => 'required|string',
    ];

    protected static $otpResendRules = [
        'version'                     => 'required|string|in:v.0.0',
        'merchantTxnId'               => 'required|string|max:30',
        'acsTxnId'                    => 'required|string|max:30',
        'resendCount'                 => 'required|numeric|in:0,1,2,3',
        'messageHash'                 => 'required|string',
    ];
}
