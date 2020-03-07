<?php

namespace RZP\Gateway\Upi\Icici\Mock;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $authRules = [
        'merchantId'           => 'numeric|max:9999999999',
        'merchantName'         => 'alpha_num|max:50',
        'subMerchantId'        => 'sometimes|alpha_num|max:10',
        'subMerchantName'      => 'sometimes|alpha_space_num|max:50',
        'terminalId'           => 'sometimes|digits_between:1,10',
        'merchantTranId'       => 'required|alpha_num|max:20',
        'billNumber'           => 'sometimes|alpha_num|max:50',
        'payerVa'              => 'sometimes|max:255',
        'amount'               => ['required', 'regex:/^\d*(\.\d{2})$/'],
        'note'                 => 'sometimes|string|max:50',
        'collectByDate'        => 'sometimes|string|max:255',
        'ValidatePayerAccFlag' => 'sometimes|in:Y,N',
        'validatePayerAccFlag' => 'sometimes|in:Y,N',
        'payerAccount'         => 'required_if:ValidatePayerAccFlag,Y',
        'payerIFSC'            => 'required_if:ValidatePayerAccFlag,Y|size:11',
    ];

    protected static $verifyRules = [
        'merchantId'        => 'numeric|max:9999999999',
        'merchantTranId'    => 'required|alpha_num|max:20',
        'subMerchantId'     => 'sometimes|alpha_num|max:10',
        'terminalId'        => 'sometimes|digits_between:1,10'
    ];

    protected static $refundRules = [
        'merchantId'                => 'numeric|max:9999999999',
        'subMerchantId'             => 'required|alpha_num|max:10',
        'terminalId'                => 'required|digits_between:1,10',
        'originalBankRRN'           => 'required|alpha_num|max:50',
        'merchantTranId'            => 'required|string|max:20',
        'originalmerchantTranId'    => 'required|alpha_num|max:20',
        'refundAmount'              => ['required', 'regex:/^\d*(\.\d{2})$/'],
        'note'                      => 'required|string|max:50',
        'onlineRefund'              => 'required|string|in:Y,N'
    ];
}
