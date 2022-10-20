<?php

namespace RZP\Services\Wallet;

use RZP\Base;

class Validator extends Base\Validator
{
    protected static $rechargeRules = [
        'merchant_id'       => 'required|alpha_num|size:14',
        'user_id'           => 'required|alpha_num|size:14',
        'transfer_id'       => 'required|alpha_num|size:14',
        'amount'            => 'required|integer|min:0',
        'notes'             => 'sometimes|string',
    ];

    protected static $paymentRules = [
        'merchant_id'       => 'required|alpha_num|size:14',
        'user_id'           => 'sometimes|alpha_num|size:14',
        'payment_id'        => 'required|alpha_num|size:14',
        'amount'            => 'required|integer|min:0',
        'customer_consent'  => 'required|bool',
        'notes'             => 'sometimes|string',
        'contact'           => 'sometimes|contact_syntax',
    ];

    protected static $refundRules = [
        'merchant_id'       => 'required|alpha_num|size:14',
        'user_id'           => 'required|alpha_num|size:14',
        'refund_id'         => 'required|alpha_num|size:14',
        'payment_id'        => 'required|alpha_num|size:14',
        'amount'            => 'required|integer|min:0',
        'notes'             => 'sometimes|string',
    ];

    protected static $transferRules = [
        'merchant_id'       => 'required|alpha_num|size:14',
        'user_id'           => 'required|alpha_num|size:14',
        'reference_id'      => 'required|alpha_num|size:14',
        'utr'               => 'required|string|size:64',
        'amount'            => 'required|integer|min:0',
        'notes'             => 'sometimes|string',
    ];

    protected static $captureRules = [
        'payment_id'        => 'required|alpha_num|size:14',
        'amount'            => 'required|integer|min:0',
    ];
}
