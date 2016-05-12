<?php

namespace Gateway\Wallet\Payumoney\Mock;

use Models\Base;

class Validator extends Base\Validator
{
    protected static $authorizeRules   = array(
        'paymentId'             => 'required|string',
        'accessToken'           => 'required|string'
    );

    protected static $debitWalletRules = array(
        'key'                   => 'required|string',
        'totalAmount'           => 'required|numeric',
        'client_id'             => 'required|string',
        'merchantTransactionId' => 'required|string',
        'hash'                  => 'required|regex:"^[a-f0-9]+$"'
    );

    protected static $refundRules = array(
        'merchantKey'           => 'required|string',
        'paymentId'             => 'required|string',
        'refundAmount'          => 'required|numeric'
    );

    protected static $verifyRules = array(
        'client_id'             => 'required|string',
        'hash'                  => 'required|regex:"^[a-f0-9]+$"',
        'merchantTransactionId' => 'required|string'
    );

    protected static $generateotpRules = array(
        'email'                 => 'required|email',
        'mobile'                => 'required|regex:"^[789]\d{9}$"',
        'client_id'             => 'required|string',
        'hash'                  => 'required|regex:"^[a-f0-9]+$"'
    );

    protected static $otpsubmitRules = array(
        'email'                 => 'required|email',
        'mobile'                => 'required|string|regex:"^[789]\d{9}$"',
        'client_id'             => 'required|string',
        'otp'                   => 'required|string|regex:"^\d{6}$"',
        'hash'                  => 'required|string|regex:"^[a-f0-9]+$"'
    );

    protected static $getBalanceRules = array(
        'email'                 => 'required|email',
        'client_id'             => 'required|string',
        'hash'                  => 'required|string|regex:"^[a-f0-9]+$"'
    );

    protected static $topupWalletRules = array(
        'key'                   => 'required|string',
        'totalAmount'           => 'required|numeric',
        'txnDetails'            => 'required|array',
        'client_id'             => 'required|string'
    );
}
