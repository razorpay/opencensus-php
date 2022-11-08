<?php

namespace RZP\Gateway\Netbanking\Axis\Emandate;

use RZP\Error\ErrorCode;
use RZP\Models\Customer\Token;

class StatusCode
{
    // Status codes in callback
    const SUCCESS = '000';
    const PENDING = '101';
    const FAILED  = '111';

    // Remarks from debit recon file
    const ERROR_INVALID_CREDS             = 'Invalid User id and Password';
    const ERROR_ACCOUNT_DORMANT           = 'Account Dormant';
    const ERROR_ACCOUNT_FROZEN            = 'Account Frozen';
    const ERROR_ACCOUNT_TYPE_MISMATCH     = 'Account Type Mismatch';
    const ERROR_TRANSACTION_RETURN_REASON = 'Transaction Return reason';
    const ERROR_DEBIT_LIMIT_EXCEEDED      = 'BSBDA Debit Limit exceeded';
    const ERROR_DEBIT_ACCOUNT_FROZEN      = 'Debit Account Frozen';
    const ERROR_LIABLE_EXCEEDS            = 'LIAB.EXCDS GRP LIMIT. NOT ALWD';
    const ERROR_INVALID_MANDATE           = 'Mandate does not Exist / Expired';
    const ERROR_MAX_DEBIT_COUNT           = 'Max debit count exceeded for this account';
    const ERROR_NO_FUNDS                  = 'No Funds Available';
    const ERROR_NO_FUNDS_IN_ACCOUNT       = 'Fund is not available in the account specified.';
    const ERROR_VALIDATION_FAILED         = 'Maximum length validation failed (Format validation failed)';
    const ERROR_LIMIT_EXPD                = 'SANCT LIMIT EXPD. OFFICERABOVE';
    const ERROR_TRANSACTION_AMOUNT_EXCEED = 'Transaction Amount is greater than Mandate Ceiling Amount';
    const ERROR_TOTAL_FREEZE              = 'Total Freeze';
    const ERROR_BALANCE_INSUFFICIENT      = 'Balance Insufficient';


    const EMANDATE_FAILURE = '0';

    const EMANDATE_REGISTRATION_SUCCESS = 'EMANDATE_REGISTRATION_SUCCESS';
    const EMANDATE_REGISTRATION_FAILURE = 'EMANDATE_REGISTRATION_FAILURE';

    const SI_STATUS_TO_RECURRING_STATUS_MAP = [
        self::SUCCESS => Token\RecurringStatus::CONFIRMED,
        self::PENDING => Token\RecurringStatus::INITIATED,
        self::FAILED  => Token\RecurringStatus::REJECTED,
    ];

    protected static $errorDescMap = [
        'Internal Server Error'                => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
        'Could not connect to host'            => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
        'Error Fetching http headers'          => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
        'Server was unable to process request. ---> Cannot access a closed file.'
                                               => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
        'Account Mismatch/Failed'              => ErrorCode::BAD_REQUEST_PAYMENT_INVALID_ACCOUNT,
    ];

    protected static $debitErrorDescMap = [
        self::ERROR_INVALID_CREDS             => ErrorCode::BAD_REQUEST_USER_NOT_AUTHENTICATED,
        self::ERROR_ACCOUNT_DORMANT           => ErrorCode::BAD_REQUEST_PAYMENT_ACCOUNT_WITHDRAWAL_FROZEN,
        self::ERROR_ACCOUNT_FROZEN            => ErrorCode::BAD_REQUEST_PAYMENT_ACCOUNT_WITHDRAWAL_FROZEN,
        self::ERROR_ACCOUNT_TYPE_MISMATCH     => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS,
        self::ERROR_TRANSACTION_RETURN_REASON => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
        self::ERROR_DEBIT_LIMIT_EXCEEDED      => ErrorCode::BAD_REQUEST_PAYMENT_ACCOUNT_MAX_LIMIT_EXCEEDED,
        self::ERROR_DEBIT_ACCOUNT_FROZEN      => ErrorCode::BAD_REQUEST_PAYMENT_ACCOUNT_WITHDRAWAL_FROZEN,
        self::ERROR_LIABLE_EXCEEDS            => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
        self::ERROR_INVALID_MANDATE           => ErrorCode::BAD_REQUEST_EMANDATE_CANCELLED_INACTIVE,
        self::ERROR_MAX_DEBIT_COUNT           => ErrorCode::BAD_REQUEST_PAYMENT_ACCOUNT_MAX_LIMIT_EXCEEDED,
        self::ERROR_NO_FUNDS                  => ErrorCode::BAD_REQUEST_PAYMENT_ACCOUNT_INSUFFICIENT_BALANCE,
        self::ERROR_NO_FUNDS_IN_ACCOUNT       => ErrorCode::BAD_REQUEST_PAYMENT_ACCOUNT_INSUFFICIENT_BALANCE,
        self::ERROR_VALIDATION_FAILED         => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS,
        self::ERROR_LIMIT_EXPD                => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
        self::ERROR_TRANSACTION_AMOUNT_EXCEED => ErrorCode::BAD_REQUEST_PAYMENT_ACCOUNT_MAX_LIMIT_EXCEEDED,
        self::ERROR_TOTAL_FREEZE              => ErrorCode::BAD_REQUEST_PAYMENT_ACCOUNT_WITHDRAWAL_FROZEN,
        self::ERROR_BALANCE_INSUFFICIENT      => ErrorCode::BAD_REQUEST_PAYMENT_ACCOUNT_INSUFFICIENT_BALANCE,
    ];

    public static function isSuccess(string $statusCode)
    {
        return ($statusCode === self::SUCCESS);
    }

    /**
     * If the registration fails, the value in mandate number would be 0,
     * else, it would be the mandate number.
     *
     * We're creating custom statuses for emandate success and failure by checking
     * the above mandate number
     *
     * @param string $mandateNumber
     *
     * @return string
     */
    public static function getEmandateStatus(string $mandateNumber)
    {
        if (self::isEmandateRegistrationSuccess($mandateNumber) === true)
        {
            return self::EMANDATE_REGISTRATION_SUCCESS;
        }

        return self::EMANDATE_REGISTRATION_FAILURE;
    }

    public static function isEmandateRegistrationSuccess(string $statusCode)
    {
        return ($statusCode !== self::EMANDATE_FAILURE);
    }

    public static function getErrorCodeMap($content)
    {
        $errorCode = $content[ResponseFields::STATUS_CODE];

        if ($errorCode === self::PENDING)
        {
            return ErrorCode::BAD_REQUEST_PAYMENT_PENDING;
        }

        // If error code is failed, we get the error mapping from the description
        else if ($errorCode === self::FAILED)
        {
            $errorDesc = $content[ResponseFields::REMARKS];

            return self::$errorDescMap[$errorDesc] ?? ErrorCode::GATEWAY_ERROR_REQUEST_ERROR;
        }

        return ErrorCode::GATEWAY_ERROR_UNKNOWN_ERROR;
    }

    public static function getEmandateDebitErrorDesc($errorDesc)
    {
        return self::$debitErrorDescMap[$errorDesc] ?? ErrorCode::BAD_REQUEST_PAYMENT_FAILED;
    }

    /**
     * Sets the SI Message
     *
     * @param string $status
     * @return string
     */
    public static function getSiMessage(string $status): string
    {
        return ($status === self::SUCCESS) ? 'Success' : 'Failure';
    }
}
