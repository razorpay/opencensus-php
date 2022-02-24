<?php

namespace RZP\Gateway\Netbanking\Hdfc;

use RZP\Error;

class ErrorCode
{
    const TRANSFER_TERMINATED_BY_USER           = 'Funds transfer terminated by user';
    const ACCOUNT_CLOSED                        = '88-Account Is Closed';
    const ACCOUNT_MISMATCH                      = 'A/c no. mismatch';
    const ACCOUNT_BLOCKED                       = 'Account Is Blocked';
    const ACCOUNT_DORMANT                       = 'Account Is Dormant';
    const ACCOUNT_NO_DEBIT_ALLOWED              = 'Account Is No Dr Allowed';
    const INSUFFICINET_FUNDS                    = 'Insufficient Funds';
    const TXN_AMT_GREATER_THAN_REGISTERED_AMT   = 'Transaction amt. is greater than the registered amt.';
    const MONTHLY_DEBIT_EXCEEDED_FOR_TXN        = 'For Monthly frequency-Monthly debit limit exceeded the transaction .';

    const USER_NOT_REGISTERED                   = 'Transaction failed.TPT/Secure access facility not registered. Please login to NetBanking and click on Third Party Transfer Tab to register, or contact phone Banking/nearest branch for more details';
    const INSUFFICIENT_BALANCE                  = 'Sorry, you do not have sufficient funds to carry out this instruction.';
    const LIMIT_EXCEEDED                        = 'You have exceeded your third party funds transfer limit for the day. You cannot transfer any more funds.';
    const TPT_NOT_REGISTERED                    = 'Third Party Funds Transfer facility not registered. Please contact Phone banking or visit nearest branch for more details.';
    const FUNDS_TRANSFER_TERMINATED_BY_USER     = 'Funds transfer terminated by user.';
    const NOT_AUTHORIZED                        = 'YOU ARE NOT AUTHORIZED TO DO THIS TRANSACTION. PLEASE LOGIN TO HDFC BANK NETBANKING TO RESET YOUR PROFILE USING \'MODIFY SECURE ACCESS PROFILE\' OR CONTACT HDFC BANK PHONEBANKING FOR FURTHER DETAILS.';
    const ACCOUNT_INVALID                       = 'No Valid account to complete this transaction.';
    const OTP_INCORRECT                         = 'OTP entered was incorrect. Please try again.';
    const UNAUTHORIZED                          = 'You are not authorized to do this transaction. Please contact the Bank.';
    const UNABLE_TO_FETCH_STATUS                = 'Unfortunately, we could not receive the status of this transaction. We request you to kindly check your Account Balance/ Statement to confirm the transaction status. If the amount is not debited from (or credited to) your account, kindly re-initiate this ';
    const FACILITY_UNAVAILABLE                  = 'We apologize, but this facility is temporarily unavailable. Please try again later.';
    const AMOUNT_EXCEEDED                       = 'Amount exceeded the authorized merchant limit, Please contact the concerned website.';
    const SECURE_ACCESS_NOT_ENABLED             = 'Secure Access not enabled. Please log into Netbanking to complete the Secure Access registration.';
    const UNABLE_TO_CARRY_INSTRUCTION           = 'We are unable to carry out your instruction. Please contact the bank.';
    const ACCOUNT_NOT_ACTIVATED                 = 'Your account has not been activated for NetBanking. Please contact the Bank.';
    const DUPLICATE_TRANSACTION                 = 'Sorry, this is a duplicate transaction. A transaction with the same details has already been processed.';
    const NOT_SUFFICIENT_BALANCE                = 'Hey! We regret to inform you that you do not have a sufficient balance in your account to complete this transaction.';


    protected static $errorMap = [
        self::TRANSFER_TERMINATED_BY_USER         => Error\ErrorCode::BAD_REQUEST_PAYMENT_NETBANKING_CANCELLED_BY_USER,

        // EMandate errors
        self::ACCOUNT_CLOSED                      => Error\ErrorCode::BAD_REQUEST_ACCOUNT_CLOSED,
        self::ACCOUNT_MISMATCH                    => Error\ErrorCode::BAD_REQUEST_ACCOUNT_NUMBER_MISMATCH,
        self::ACCOUNT_BLOCKED                     => Error\ErrorCode::BAD_REQUEST_ACCOUNT_BLOCKED,
        self::ACCOUNT_DORMANT                     => Error\ErrorCode::BAD_REQUEST_ACCOUNT_DORMANT,
        self::ACCOUNT_NO_DEBIT_ALLOWED            => Error\ErrorCode::BAD_REQUEST_NO_DR_ALLOWED,
        self::INSUFFICINET_FUNDS                  => Error\ErrorCode::BAD_REQUEST_PAYMENT_ACCOUNT_INSUFFICIENT_BALANCE,
        self::TXN_AMT_GREATER_THAN_REGISTERED_AMT => Error\ErrorCode::BAD_REQUEST_TRANSACTION_AMOUT_GREATER_THAN_REGISTERED_AMOUNT,
        self::MONTHLY_DEBIT_EXCEEDED_FOR_TXN      => Error\ErrorCode::BAD_REQUEST_FREQUENCY_DEBIT_LIMIT_EXCEEDED,
    ];

    protected static $hdfcNetbankingErrorMapping = [
        self::USER_NOT_REGISTERED                 => Error\ErrorCode::BAD_REQUEST_NETBANKING_USER_NOT_REGISTERED,
        self::INSUFFICIENT_BALANCE                => Error\ErrorCode::BAD_REQUEST_PAYMENT_ACCOUNT_INSUFFICIENT_BALANCE,
        self::LIMIT_EXCEEDED                      => Error\ErrorCode::BAD_REQUEST_TRANSACTION_AMOUNT_LIMIT_EXCEEDED,
        self::TPT_NOT_REGISTERED                  => Error\ErrorCode::BAD_REQUEST_NETBANKING_USER_NOT_REGISTERED,
        self::FUNDS_TRANSFER_TERMINATED_BY_USER   => Error\ErrorCode::BAD_REQUEST_PAYMENT_NETBANKING_CANCELLED_BY_USER,
        self::NOT_AUTHORIZED                      => Error\ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
        self::ACCOUNT_INVALID                     => Error\ErrorCode::BAD_REQUEST_PAYMENT_INVALID_ACCOUNT,
        self::OTP_INCORRECT                       => Error\ErrorCode::BAD_REQUEST_PAYMENT_OTP_INCORRECT,
        self::UNAUTHORIZED                        => Error\ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
        self::UNABLE_TO_FETCH_STATUS              => Error\ErrorCode::BAD_REQUEST_PAYMENT_PENDING,
        self::FACILITY_UNAVAILABLE                => Error\ErrorCode::BAD_REQUEST_PAYMENT_BANK_SYSTEM_ERROR,
        self::AMOUNT_EXCEEDED                     => Error\ErrorCode::GATEWAY_ERROR_TERMINAL_MAX_TRANSACTION_LIMIT_REACHED,
        self::SECURE_ACCESS_NOT_ENABLED           => Error\ErrorCode::BAD_REQUEST_NETBANKING_USER_NOT_REGISTERED,
        self::UNABLE_TO_CARRY_INSTRUCTION         => Error\ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
        self::ACCOUNT_NOT_ACTIVATED               => Error\ErrorCode::BAD_REQUEST_NETBANKING_USER_NOT_REGISTERED,
        self::DUPLICATE_TRANSACTION               => Error\ErrorCode::GATEWAY_ERROR_PAYMENT_DUPLICATE_REQUEST,
        self::NOT_SUFFICIENT_BALANCE              => Error\ErrorCode::BAD_REQUEST_PAYMENT_ACCOUNT_INSUFFICIENT_BALANCE,
    ];

    public static function getApiErrorCode(string $error): string
    {
        return (self::$errorMap[$error] ?? Error\ErrorCode::BAD_REQUEST_PAYMENT_FAILED);
    }

    public static function getHdfcNetbankingErrorCodes(string $error): string
    {
        if (isset(self::$hdfcNetbankingErrorMapping[$error]) === true)
        {
            return self::$hdfcNetbankingErrorMapping[$error];
        }

        return \RZP\Error\ErrorCode::GATEWAY_ERROR_UNKNOWN_ERROR;
    }
}
