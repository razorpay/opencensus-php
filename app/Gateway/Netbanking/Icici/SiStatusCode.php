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

    const STATUS_CODE_TO_INTERNAL_ERROR_CODE_MAP = [
        self::MERCHANT_NOT_ENABLED_FOR_SI => ErrorCode::GATEWAY_ERROR_MERCHANT_NOT_ENABLED_FOR_STANDING_INSTRUCTION,
        self::MERCHANT_IP_NOT_WHITELISTED => ErrorCode::GATEWAY_ERROR_MERCHANT_IP_NOT_WHITELISTED,
        self::PREMATURE_SI_EXECUTION      => ErrorCode::GATEWAY_ERROR_PREMATURE_SI_EXECUTION,
        self::SI_EXECUTION_ALREADY_DONE   => ErrorCode::GATEWAY_ERROR_SI_EXECUTION_ALREADY_DONE,
        self::TXN_PARAMETERS_NO_MATCHING  => ErrorCode::BAD_REQUEST_INVALID_PARAMETERS,
        self::TECHNICAL_ISSUE             => ErrorCode::GATEWAY_ERROR_FATAL_ERROR,
    ];

    public static function getInternalErrorCode(string $status)
    {
        return self::STATUS_CODE_TO_INTERNAL_ERROR_CODE_MAP[$status] ?? ErrorCode::BAD_REQUEST_PAYMENT_FAILED;
    }
}