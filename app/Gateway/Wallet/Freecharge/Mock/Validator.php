<?php

namespace RZP\Gateway\Wallet\Payumoney\Mock;

use RZP\Models\Base;

class Validator extends Base\Validator
{
    protected static $authorizeRules   = array(
        'paymentId'             => 'required|string',
        'accessToken'           => 'required|string|in:8c31d80b-83ed-4f52-8377-71301790ccaa'
    );

    protected static $debitWalletRules = array(
        'merchantId'            => 'required|string',
        'amount'                => 'required|numeric',
        'accessToken'           => 'required|string',
        'merchantTxnId'         => 'required|string',
        'channel'               => 'required|string|in:WEB,ANDROID,WINDOWS,IOS,WAP',
        'checksum'              => 'required|regex:"^[a-f0-9]+$"'
    );

    protected static $refundRules = array(
        'merchantId'            => 'required|string',
        'paymentId'             => 'required|string',
        'refundMerchantTxnId'   => 'required|string',
        'txnId'                 => 'required|string',
        'refundAmount'          => 'required|numeric',
        'checksum'              => 'required|regex:"^[a-f0-9]+$"',
    );

    protected static $verifyRules = array(
        'client_id'             => 'required|string',
        'checksum'              => 'required|regex:"^[a-f0-9]+$"',
        'merchantTransactionId' => 'required|string'
    );

    protected static $otpGenerateRules = array(
        'email'                 => 'required|email',
        'mobile'                => 'required|regex:"^[789]\d{9}$"',
        'merchantId'            => 'required|string',
        'checksum'              => 'required|regex:"^[a-f0-9]+$"'
    );

    protected static $otpSubmitRules = array(
        'merchantId'            => 'required|string',
        'otpId'                 => 'required|string',
        'userMachineIdentifier' => 'required|string',
        'otp'                   => 'required|string|regex:"^\d{6}$"',
        'checksum'              => 'required|string|regex:"^[a-f0-9]+$"'
    );

    protected static $getBalanceRules = array(
        'merchantId'            => 'required|string',
        'accessToken'           => 'required|string',
        'checksum'              => 'required|string|regex:"^[a-f0-9]+$"'
    );

    protected static $topupWalletRules = array(
        'merchantId'            => 'required|string',
        'amount'                => 'required|numeric',
        'channel'               => 'required|string|in:WEB,ANDROID,WINDOWS,IOS,WAP',
        'logintoken'            => 'required|string',
        'checksum'              => 'required|string|regex:"^[a-f0-9]+$"',
        'callbackUrl'           => 'required|url',
    );
}
