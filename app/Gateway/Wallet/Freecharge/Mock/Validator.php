<?php

namespace RZP\Gateway\Wallet\Freecharge\Mock;

use RZP\Base;
use RZP\Exception;
use RZP\Gateway\Wallet\Freecharge\ResponseCodeMap;

class Validator extends Base\Validator
{
    protected static $authorizeRules   = array(
        'merchantId'            => 'required|string',
        'amount'                => 'required|numeric',
        'channel'               => 'required|string|custom',
        'loginToken'            => 'required|string',
        'checksum'              => 'required|string|regex:"^[a-f0-9]+$"',
        'metadata'              => 'sometimes|string',
        'surl'                  => 'sometimes|string',
        'furl'                  => 'sometimes|string',
        'merchantTxnId'         => 'sometimes|string'
    );

    protected static $debitWalletRules = array(
        'merchantId'            => 'required|string',
        'amount'                => 'required|numeric',
        'accessToken'           => 'required|string',
        'currency'              => 'required|string',
        'merchantTxnId'         => 'required|string',
        'channel'               => 'required|string|custom',
        'dealerId'              => 'sometimes|string',
        'checksum'              => 'required|regex:"^[a-f0-9]+$"'
    );

    protected static $refundRules = array(
        'merchantId'            => 'required|string',
        'refundMerchantTxnId'   => 'required|string',
        'txnId'                 => 'required|string',
        'refundAmount'          => 'required|numeric',
        'merchantTxnId'         => 'sometimes|string',
        'checksum'              => 'required|regex:"^[a-f0-9]+$"',
    );

    protected static $verifyRules = array(
        'merchantId'            => 'required|string',
        'checksum'              => 'required|regex:"^[a-f0-9]+$"',
        'merchantTxnId'         => 'required|string',
        'txnId'                 => 'sometimes|string',
        'txnType'               => 'required|string',
    );

    protected static $otpGenerateRules = array(
        'email'                 => 'required|email',
        'mobileNumber'          => 'required|regex:"^[789]\d{9}$"',
        'merchantId'            => 'required|string',
        'checksum'              => 'required|regex:"^[a-f0-9]+$"'
    );

    protected static $otpResendRules = array(
        'channel'               => 'required|string',
        'otpId'                 => 'required|string',
        'merchantId'            => 'required|string',
        'checksum'              => 'required|string',
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

    protected function validateChannel($attribute, $value)
    {
        $channels = ['WEB', 'ANDROID', 'WINDOWS', 'IOS', 'WAP'];

        if(in_array($value, $channels, true) === false)
        {
            throw new Exception\BadRequestException(
                ResponseCodeMap::getApiErrorCode('E023'));
        }
    }
}
