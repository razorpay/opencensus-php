<?php

namespace RZP\Gateway\CardlessEmi;

use RZP\Models\Payment;
use RZP\Error\ErrorCode;

trait ErrorCodes
{
    public $errorCodeMap = [
        'USER_DNE'                         => ErrorCode::BAD_REQUEST_CARDLESS_EMI_USER_DOES_NOT_EXIST,
        'INV_TOKEN'                        => ErrorCode::BAD_REQUEST_CARDLESS_EMI_INVALID_TOKEN,
        'INV_MERCHANT_NAME'                => ErrorCode::BAD_REQUEST_CARDLESS_EMI_INVALID_MERCHANT_NAME,
        'INV_EMI_PLAN_ID'                  => ErrorCode::BAD_REQUEST_CARDLESS_EMI_INVALID_EMI_PLAN_ID,
        'MIN_AMT_REQ'                      => ErrorCode::BAD_REQUEST_CARDLESS_EMI_MINIMUM_AMOUNT_REQUIRED,
        'MAX_AMT_LMT'                      => ErrorCode::BAD_REQUEST_CARDLESS_EMI_MAXIMUM_AMOUNT_LIMIT,
        'PAYMENT_TIMED_OUT'                => ErrorCode::BAD_REQUEST_PAYMENT_TIMED_OUT,
        'PAYMENT_CANCELLED'                => ErrorCode::BAD_REQUEST_PAYMENT_CANCELLED,
        'INV_TXN_ID'                       => ErrorCode::GATEWAY_VERIFY_REFUND_ABSENT,
        'PAYMENT_FAILED_PARTNER'           => ErrorCode::GATEWAY_ERROR_CARDLESS_EMI_PAYMENT_FAILED_PARTNER,
        'PAYMENT_FAILED'                   => ErrorCode::GATEWAY_ERROR_CARDLESS_EMI_PAYMENT_FAILED_PARTNER,
        'CREDIT_LIMIT_EXHAUSTED'           => ErrorCode::BAD_REQUEST_CARDLESS_EMI_CREDIT_LIMIT_EXHAUSTED,
        'INV_CAPTURE_AMT'                  => ErrorCode::BAD_REQUEST_PAYMENT_CAPTURE_AMOUNT_NOT_EQUAL_TO_AUTH,
        'CUST_CREDIT_LIMIT_NOT_ACTIVATED'  => ErrorCode::BAD_REQUEST_CARDLESS_EMI_CREDIT_LIMIT_NOT_ACTIVATED,
        'CUST_CREDIT_LIMIT_NOT_APPROVED'   => ErrorCode::BAD_REQUEST_CARDLESS_EMI_CREDIT_LIMIT_NOT_APPROVED,
        'CUST_CREDIT_LIMIT_EXPIRED'        => ErrorCode::BAD_REQUEST_CARDLESS_EMI_CREDIT_LIMIT_EXPIRED,
        'OTP_INVALID'                      => ErrorCode::BAD_REQUEST_INCORRECT_OTP,
        'INV_AMT'                          => ErrorCode::BAD_REQUEST_INVALID_TRANSACTION_AMOUNT,
        'REFUND_FAILED'                    => ErrorCode::GATEWAY_ERROR_PAYMENT_REFUND_FAILED,
        'CREDIT_LMT_EXHAUSTED'             => ErrorCode::BAD_REQUEST_CARDLESS_EMI_CREDIT_LIMIT_EXHAUSTED,
        'RZP_INVALID_REFUND_ID'            => ErrorCode::GATEWAY_VERIFY_REFUND_ABSENT
    ];

    public $paylaterErrorCodeMap = [
        'USER_DNE'                         => ErrorCode::BAD_REQUEST_PAYLATER_USER_DOES_NOT_EXIST,
        'INV_TOKEN'                        => ErrorCode::GATEWAY_ERROR_PAYLATER_INVALID_TOKEN,
        'INV_MERCHANT_NAME'                => ErrorCode::BAD_REQUEST_PAYLATER_INVALID_MERCHANT_NAME,
        'MIN_AMT_REQ'                      => ErrorCode::BAD_REQUEST_PAYLATER_MINIMUM_AMOUNT_REQUIRED,
        'MAX_AMT_LMT'                      => ErrorCode::BAD_REQUEST_PAYLATER_MAXIMUM_AMOUNT_LIMIT,
        'PAYMENT_FAILED_PARTNER'           => ErrorCode::GATEWAY_ERROR_PAYLATER_PAYMENT_FAILED_PARTNER,
        'PAYMENT_FAILED'                   => ErrorCode::GATEWAY_ERROR_PAYLATER_PAYMENT_FAILED_PARTNER,
        'CREDIT_LIMIT_EXHAUSTED'           => ErrorCode::BAD_REQUEST_PAYLATER_CREDIT_LIMIT_EXHAUSTED,
        'CUST_CREDIT_LIMIT_NOT_ACTIVATED'  => ErrorCode::BAD_REQUEST_PAYLATER_CREDIT_LIMIT_NOT_ACTIVATED,
        'CUST_CREDIT_LIMIT_NOT_APPROVED'   => ErrorCode::BAD_REQUEST_PAYLATER_CREDIT_LIMIT_NOT_APPROVED,
        'CUST_CREDIT_LIMIT_EXPIRED'        => ErrorCode::BAD_REQUEST_PAYLATER_CREDIT_LIMIT_EXPIRED,
        'CREDIT_LMT_EXHAUSTED'             => ErrorCode::BAD_REQUEST_PAYLATER_CREDIT_LIMIT_EXHAUSTED,
    ];

    public function getInternalErrorCode($errorCode, $defaultErrorCode)
    {
        if ($this->gateway === Payment\Gateway::PAYLATER)
        {
             if (isset($this->paylaterErrorCodeMap[$errorCode]) === true)
             {
                 return $this->paylaterErrorCodeMap[$errorCode];
             }
        }

        if (isset($this->errorCodeMap[$errorCode]) === true)
        {
            return $this->errorCodeMap[$errorCode];
        }

        return $defaultErrorCode;
    }
}
