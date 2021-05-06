<?php

namespace RZP\Gateway\Netbanking\Icici;

use RZP\Error\ErrorCode;

class SiStatusCode
{
    const MERCHANT_NOT_ENABLED_FOR_SI = 'Merchant is not enabled for Standing Instruction Payment. ' .
                                        'Please click OK to initiate one-time net banking transaction';

    const MERCHANT_IP_NOT_WHITELISTED = '968/Payee ID and IP Address are not matching';

    const PREMATURE_SI_EXECUTION      = 'PaymentDateOverdue';

    const SI_EXECUTION_ALREADY_DONE   = 'PaymentAlreadyDone';

    const TXN_PARAMETERS_NO_MATCHING  = 'NoSuchPaymentScheduled';

    const TECHNICAL_ISSUE             = 'Error: Technical issue in processing, kindly try after sometime \n' .
                                        'OR \n' .
                                        'Please click OK to initiate one-time net banking transaction';

    const INVALID_MESSAGE_FORMAT      = 'Host Invalid Message Format/Invalid Message Format';

    const SERVICE_UNAVAILABLE         = 'Currently this service is unavailable. '.
                                        'We regret the inconvenience caused. Please try after some time.' .
                                        '/Type system exception occurred in transaction layer.';

    const INSUFFICIENT_FUNDS          = 'There are no sufficient funds in the debit account selected. ' .
                                        'Please select another account./Insufficient Funds';

    const INSUFFICIENT_BALANCE         = 'There are no sufficient funds in the debit account selected. ' .
                                         'Please select another account with sufficient balance and try again.' .
                                         '/Insufficient Funds';


    const PAYMENT_STOPPED_BY_CUST     = 'PaymentStoppedByCustomer';

    const UNABLE_TO_PROCESS           = 'We are currently unable to process your request. Please try after sometime.';

    const USER_DISABLED               = 'User is Disabled/USER IS DISABLED';

    const STATUS_CODE_TO_INTERNAL_ERROR_CODE_MAP = [
        self::MERCHANT_NOT_ENABLED_FOR_SI => ErrorCode::GATEWAY_ERROR_MERCHANT_NOT_ENABLED_FOR_STANDING_INSTRUCTION,
        self::MERCHANT_IP_NOT_WHITELISTED => ErrorCode::GATEWAY_ERROR_MERCHANT_IP_NOT_WHITELISTED,
        self::PREMATURE_SI_EXECUTION      => ErrorCode::GATEWAY_ERROR_PREMATURE_SI_EXECUTION,
        self::SI_EXECUTION_ALREADY_DONE   => ErrorCode::GATEWAY_ERROR_SI_EXECUTION_ALREADY_DONE,
        self::TXN_PARAMETERS_NO_MATCHING  => ErrorCode::BAD_REQUEST_INVALID_PARAMETERS,
        self::TECHNICAL_ISSUE             => ErrorCode::GATEWAY_ERROR_FATAL_ERROR,
        self::INVALID_MESSAGE_FORMAT      => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS,
        self::SERVICE_UNAVAILABLE         => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
        self::INSUFFICIENT_FUNDS          => ErrorCode::BAD_REQUEST_PAYMENT_ACCOUNT_INSUFFICIENT_BALANCE,
        self::PAYMENT_STOPPED_BY_CUST     => ErrorCode::BAD_REQUEST_PAYMENT_CANCELLED_BY_CUSTOMER,
        self::UNABLE_TO_PROCESS           => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
        self::USER_DISABLED               => ErrorCode::BAD_REQUEST_PAYMENT_INVALID_ACCOUNT,
        self::INSUFFICIENT_BALANCE        => ErrorCode::BAD_REQUEST_PAYMENT_ACCOUNT_INSUFFICIENT_BALANCE,
    ];

    public static function getInternalErrorCode(string $status)
    {
        return self::STATUS_CODE_TO_INTERNAL_ERROR_CODE_MAP[$status] ?? ErrorCode::BAD_REQUEST_PAYMENT_FAILED;
    }
}
