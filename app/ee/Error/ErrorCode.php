<?php

namespace EE\Error;

class ErrorCode
{
    /**
     * The error codes are named such that the first word
     * tells where the error is supposed to originate.
     * Right now it can be GATEWAY, API, EE, Trace.
     *
     * The second word tells us what the error is related to.
     * Which can be either CARD, TRANSACTION, etc.
     */

    /**
     * General gateway specific error codes
     * Isn't specific to one particular gateway and
     * should be generally valid for gateways that
     * are added in future
     */
    const GATEWAY_REQUEST_TIMEOUT                           = 'GATEWAY_REQUEST_TIMEOUT';
    const GATEWAY_AUTHENTICATION_NOT_AVAILABLE              = 'GATEWAY_AUTHENTICATION_NOT_AVAILABLE';
    const GATEWAY_INVALID_TERMINAL_ID                       = 'GATEWAY_INVALID_TERMINAL_ID';
    const GATEWAY_CERTIFICATE_VALIDATION_FAILED             = 'GATEWAY_CERTIFICATE_VALIDATION_FAILED';
    const GATEWAY_SIGNATURE_VALIDATION_FAILED               = 'GATEWAY_SIGNATURE_VALIDATION_FAILED';
    const GATEWAY_INVALID_SUBSEQUENT_TRANSACTION            = 'GATEWAY_INVALID_SUBSEQUENT_TRANSACTION';
    const GATEWAY_NOT_UNDERSTOOD_ERROR                      = 'GATEWAY_NOT_UNDERSTOOD_ERROR';
    const GATEWAY_UNKNOWN_ERROR                             = 'GATEWAY_UNKNOWN_ERROR';

    const GATEWAY_TRANSACTION_DUPLICATE_REQUEST             = 'GATEWAY_TRANSACTION_DUPLICATE_REQUEST';
    const GATEWAY_TRANSACTION_MISSING_DATA                  = 'GATEWAY_TRANSACTION_MISSING_DATA';
    const GATEWAY_TRANSACTION_INVALID_ACTION                = 'GATEWAY_TRANSACTION_INVALID_ACTION';
    const GATEWAY_TRANSACTION_INVALID_ID                    = 'GATEWAY_TRANSACTION_INVALID_ID';
    const GATEWAY_TRANSACTION_DENIED_NEGATIVE_BIN           = 'GATEWAY_TRANSACTION_DENIED_NEGATIVE_BIN';
    const GATEWAY_TRANSACTION_INVALID_CURRENCY              = 'GATEWAY_TRANSACTION_INVALID_CURRENCY';
    const GATEWAY_TRANSACTION_INVALID_UDF                   = 'GATEWAY_TRANSACTION_INVALID_UDF';
    const GATEWAY_TRANSACTION_CREDIT_LESS_THAN_DEBIT        = 'GATEWAY_TRANSACTION_CREDIT_LESS_THAN_DEBIT';
    const GATEWAY_TRANSACTION_SUPPORT_FAILED                = 'GATEWAY_TRANSACTION_SUPPORT_FAILED';
    const GATEWAY_TRANSACTION_SUPPORT_AUTH_NOT_FOUND        = 'GATEWAY_TRANSACTION_SUPPORT_AUTH_NOT_FOUND';
    const GATEWAY_TRANSACTION_PARES_NOT_SUCCESFUL           = 'GATEWAY_TRANSACTION_PARES_NOT_SUCCESFUL';

    const GATEWAY_CARD_INVALID_NAME                         = 'GATEWAY_CARD_INVALID_NAME';
    const GATEWAY_CARD_INVALID_NUMBER                       = 'GATEWAY_CARD_INVALID_NUMBER';
    const GATEWAY_CARD_INVALID_EXPIRY_DATE                  = 'GATEWAY_CARD_INVALID_EXPIRY_DATE';
    const GATEWAY_CARD_INVALID_BRAND                        = 'GATEWAY_CARD_INVALID_BRAND';
    const GATEWAY_CARD_INVALID_AMOUNT                       = 'GATEWAY_CARD_INVALID_AMOUNT';
    const GATEWAY_CARD_INVALID_ADDRESS                      = 'GATEWAY_CARD_INVALID_ADDRESS';
    const GATEWAY_CARD_INVALID_ZIP                          = 'GATEWAY_CARD_INVALID_ZIP';
    const GATEWAY_CARD_INVALID_CVV                          = 'GATEWAY_CARD_INVALID_CVV';
    const GATEWAY_CARD_MISSING_CVV                          = 'GATEWAY_CARD_MISSING_CVV';
    const GATEWAY_CARD_DECLINED                             = 'GATEWAY_CARD_DECLINED';

    /**
     * Card errors catchable in the app
     */
    const CARD_ERROR_INVALID_NAME             = 'CARD_ERROR_INVALID_NAME';
    const CARD_ERROR_INVALID_EXPIRY_MONTH     = 'CARD_ERROR_INVALID_EXPIRY_MONTH';
    const CARD_ERROR_INVALID_EXPIRY_YEAR      = 'CARD_ERROR_INVALID_EXPIRY_YEAR';
    const CARD_ERROR_INVALID_CVV              = 'CARD_ERROR_INVALID_CVV';
    const CARD_ERROR_INVALID_BRAND            = 'CARD_ERROR_INVALID_BRAND';
    const CARD_ERROR_INVALID_AMOUNT           = 'CARD_ERROR_INVALID_AMOUNT';
    const CARD_ERROR_INVALID_NUMBER           = 'CARD_ERROR_INVALID_NUMBER';
    const CARD_ERROR_INVALID_EXPIRY_DATE      = 'CARD_ERROR_INVALID_EXPIRY_DATE';

    const UDF_ERROR_INVALID_EMAIL               = 'UDF_ERROR_INVALID_EMAIL';
    const UDF_ERROR_INVALID_CONTACT             = 'UDF_ERROR_INVALID_CONTACT';

    const BAD_REQUEST_EXTRA_FIELDS_PROVIDED     = 'BAD_REQUEST_EXTRA_FIELDS_PROVIDED';
    const BAD_REQUEST_ERROR                     = 'BAD_REQUEST_ERROR';

    const API_TRANSACTION_INVALID_CURRENCY  = 'API_TRANSACTION_INVALID_CURRENCY';
    const API_TRANSACTION_INVALID_AMOUNT    = 'API_TRANSACTION_INVALID_AMOUNT';

    const DB_RECORD_NOT_FOUND               = 'DB_RECORD_NOT_FOUND';
    const DB_QUERY_FAILED                   = 'DB_QUERY_FAILED';
    const DB_QUERY_INVALID_SYNTAX           = 'DB_QUERY_INVALID_SYNTAX';

    public static $gatewayUncatchableErrors = array(
        self::GATEWAY_CARD_INVALID_AMOUNT,
        self::GATEWAY_CARD_INVALID_ADDRESS,
        self::GATEWAY_CARD_INVALID_ZIP_CODE,
        self::GATEWAY_CARD_DECLINED,
        self::GATEWAY_TXN_DENIED_NEGATIVE_BIN);

    public static function isGatewayError($error)
    {
        ;
    }

    public static function isCardError($error)
    {
        return true;
    }

    public static function errorCodeExists($code)
    {
        if (defined(__CLASS__.'::'.$code))
        {
            return true;
        }
        else
        {
            return false;
        }
    }
}