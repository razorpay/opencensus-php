<?php

namespace RZP\Gateway\Mobikwik;

use RZP\Error;
use RZP\Error\ErrorCode;
use RZP\Models\Payment\TwoFaStatus;

class ResponseCodeMap
{
    public static $codes = array(
        '0'  => 'Transaction completed successfully',
        '10' => ErrorCode::GATEWAY_ERROR_INVALID_TERMINAL_SECRET,
        '20' => ErrorCode::BAD_REQUEST_PAYMENT_WALLET_BLOCKED_CUSTOMER,
        '21' => 'Merchant Blocked',
        '22' => 'Merchant does not Exist',
        '23' => 'Merchant not registered on MobiKwik',
        '24' => 'Orderid is Blank or Null',
        '30' => 'Wallet TopUp Failed',
        '31' => 'Wallet Debit Failed',
        '32' => 'Wallet Credit Failed',
        '33' => ErrorCode::BAD_REQUEST_PAYMENT_WALLET_INSUFFICIENT_BALANCE,
        '40' => ErrorCode::BAD_REQUEST_PAYMENT_CANCELLED_AT_LOGIN_SCREEN,
        '41' => ErrorCode::BAD_REQUEST_PAYMENT_CANCELLED_AT_WALLET_PAYMENT_PAGE,
        '42' => ErrorCode::BAD_REQUEST_PAYMENT_CANCELLED_AT_WALLET_PAYMENT_PAGE,
        '50' => ErrorCode::GATEWAY_ERROR_PAYMENT_DUPLICATE_REQUEST,
        '51' => 'Length of parameter orderid must be between 8 to 30 characters',
        '52' => 'Parameter orderid must be alphanumeric only',
        '53' => ErrorCode::GATEWAY_ERROR_PAYMENT_INVALID_EMAIL,
        '54' => ErrorCode::GATEWAY_ERROR_PAYMENT_INVALID_AMOUNT,
        '55' => ErrorCode::GATEWAY_ERROR_PAYMENT_INVALID_MOBILE,
        '56' => 'Parameter merchantname is invalid. It must be alphanumeric and its length must be between 1 to 30 characters',
        '57' => 'Parameter redirecturl is invalid',
        '60' => ErrorCode::BAD_REQUEST_PAYMENT_WALLET_AUTHENTICATION_FAILED,
        '70' => ErrorCode::BAD_REQUEST_PAYMENT_WALLET_PER_MONTH_LIMIT_EXCEEDED,
        '71' => ErrorCode::BAD_REQUEST_PAYMENT_WALLET_PER_MONTH_LIMIT_EXCEEDED,
        '72' => 'Maximum amount per transaction limit for this merchant crossed',
        '73' => 'Merchant is not allowed to perform transactions on himself',
        '74' => 'KYC Transactions is not allowed',
        '80' => ErrorCode::GATEWAY_ERROR_CHECKSUM_MATCH_FAILED,
        '99' => ErrorCode::GATEWAY_ERROR_FATAL_ERROR,
        '110' => 'Invalid action parameter',
        '120' => ErrorCode::BAD_REQUEST_PAYMENT_WALLET_USER_DOES_NOT_EXIST,
        '148' => ErrorCode::BAD_REQUEST_PAYMENT_OTP_VALIDATION_ATTEMPT_LIMIT_EXCEEDED,
        '150' => 'Invalid Message Code',
        '151' => 'Invalid Request Parameters',
        '152' => ErrorCode::BAD_REQUEST_PAYMENT_WALLET_ACCOUNT_ALREADY_EXIST_WITH_EMAIL,
        '153' => ErrorCode::BAD_REQUEST_PAYMENT_WALLET_ACCOUNT_ALREADY_EXIST_WITH_CONTACT,
        '155' => ErrorCode::BAD_REQUEST_PAYMENT_OTP_INCORRECT,
        '156' => ErrorCode::BAD_REQUEST_PAYMENT_WALLET_ACCOUNT_INVALID_MOBILE,
        '157' => 'Either Email or Mobile is required for OTP generation',
        '158' => 'Provide either Email or cell to uniquely identify you',
        '159' => ErrorCode::BAD_REQUEST_PAYMENT_WALLET_USER_DOES_NOT_EXIST,
        '160' => 'Our record suggests that no mobile is registered with your email',
        '164' => ErrorCode::BAD_REQUEST_PAYMENT_OTP_INCORRECT,
        '170' => 'Wallet is not semi closed',
    );

    protected static $success = array(
        1, 8,
    );

    public static function getTwoFaStatus($code)
    {
        switch ($code) {
            case '0':
                return TwoFaStatus::PASSED;
            case '164':
            case '155':
            case '148':
            case '60':
                return TwoFaStatus::FAILED;
            default:
                return TwoFaStatus::UNKNOWN;
        }
    }

    public static function isWalletUserNotPresent($code)
    {
        return self::getApiErrorCode($code) === ErrorCode::BAD_REQUEST_PAYMENT_WALLET_USER_DOES_NOT_EXIST;
    }

    public static function getResponseMessage($code)
    {
        $codes = self::$codes;

        return $codes[(int)$code];
    }

    public static function getStatus($code)
    {
        ; // @todo
    }

    public static function getApiErrorCode($code)
    {
        $class = 'RZP\Error\ErrorCode::';

        if (isset(self::$codes[$code]) === false)
        {
            return ErrorCode::BAD_REQUEST_PAYMENT_FAILED;
        }

        $apiCode = self::$codes[$code];

        if (defined($class . $apiCode))
        {
            return $apiCode;
        }

        return ErrorCode::BAD_REQUEST_PAYMENT_FAILED;
    }
}