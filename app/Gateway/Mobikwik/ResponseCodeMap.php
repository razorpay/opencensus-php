<?php

namespace RZP\Gateway\Mobikwik;

use RZP\Error;
use RZP\Error\ErrorCode;

class ResponseCodeMap
{
    public static $codes = array(
        '0'  => 'Transaction completed successfully',
        '10' => ErrorCode::GATEWAY_ERROR_INVALID_TERMINAL_SECRET,
        '20' => ErrorCode::BAD_REQUEST_PAYMENT_WALLET_BLOCKED_CUSTOMER,
        '21' => ErrorCode::GATEWAY_ERROR_INVALID_TERMINAL,
        '22' => ErrorCode::GATEWAY_ERROR_INVALID_TERMINAL,
        '23' => ErrorCode::GATEWAY_ERROR_INVALID_TERMINAL,
        '24' => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS,
        '30' => 'Wallet TopUp Failed',
        '31' => 'Wallet Debit Failed',
        '32' => 'Wallet Credit Failed',
        '33' => ErrorCode::BAD_REQUEST_PAYMENT_WALLET_INSUFFICIENT_BALANCE,
        '40' => ErrorCode::BAD_REQUEST_PAYMENT_CANCELLED_AT_LOGIN_SCREEN,
        '41' => ErrorCode::BAD_REQUEST_PAYMENT_CANCELLED_AT_WALLET_PAYMENT_PAGE,
        '42' => ErrorCode::BAD_REQUEST_PAYMENT_CANCELLED_AT_WALLET_PAYMENT_PAGE,
        '50' => ErrorCode::GATEWAY_ERROR_PAYMENT_DUPLICATE_REQUEST,
        '51' => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS,
        '52' => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS,
        '53' => ErrorCode::BAD_REQUEST_PAYMENT_INVALID_EMAIL,
        '54' => ErrorCode::GATEWAY_ERROR_PAYMENT_INVALID_AMOUNT,
        '55' => ErrorCode::BAD_REQUEST_PAYMENT_INVALID_MOBILE,
        '56' => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS,
        '57' => ErrorCode::GATEWAY_ERROR_INVALID_CALLBACK_URL,
        '60' => ErrorCode::BAD_REQUEST_PAYMENT_WALLET_AUTHENTICATION_FAILED,
        '70' => ErrorCode::BAD_REQUEST_PAYMENT_WALLET_PER_MONTH_LIMIT_EXCEEDED,
        '71' => ErrorCode::BAD_REQUEST_PAYMENT_WALLET_PER_MONTH_LIMIT_EXCEEDED,
        '72' => ErrorCode::BAD_REQUEST_PAYMENT_WALLET_PER_PAYMENT_AMOUNT_CROSSED,
        '73' => ErrorCode::GATEWAY_ERROR_TRANSACTION_TYPE_NOT_SUPPORTED,
        '74' => ErrorCode::GATEWAY_ERROR_TRANSACTION_TYPE_NOT_SUPPORTED,
        '80' => ErrorCode::GATEWAY_ERROR_CHECKSUM_MATCH_FAILED,
        '99' => ErrorCode::GATEWAY_ERROR_FATAL_ERROR,
        '110' => ErrorCode::GATEWAY_ERROR_PAYMENT_INVALID_ACTION,
        '120' => ErrorCode::BAD_REQUEST_PAYMENT_WALLET_USER_DOES_NOT_EXIST,
        '148' => ErrorCode::BAD_REQUEST_PAYMENT_OTP_VALIDATION_ATTEMPT_LIMIT_EXCEEDED,
        '150' => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS,
        '151' => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS,
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

    public static function isWalletUserNotPresent($code)
    {
        return self::getApiErrorCode($code) === ErrorCode::BAD_REQUEST_PAYMENT_WALLET_USER_DOES_NOT_EXIST;
    }

    public static function getResponseMessage($code)
    {
        $codes = self::$codes;

        return $codes[$code];
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
