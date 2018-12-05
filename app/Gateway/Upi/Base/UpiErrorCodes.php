<?php

namespace RZP\Gateway\Upi\Base;

use RZP\Error\ErrorCode;
use RZP\Gateway\Base\Action;

class UpiErrorCodes
{
    protected static $errorCodes = [
        // Error Codes
        'U28'   => 'PSP NOT AVAILABLE',
        'U88'   => 'CONNECTION TIMEOUT IN REQPAY CREDIT',
        'U68'   => 'CREDIT TIMEOUT',
        'U31'   => 'CREDIT HAS BEEN FAILED',
        // Response Codes
        'BT'    => 'Transaction is pending (BT).',
        'XY'    => 'REMITTER CBS OFFLINE',
        'XC'    => 'INVALID TRANSACTION OR IF MEMBER IS NOT ABLE TO FIND ANY APPROPRIATE RESPONSE CODE (BENEFICIARY)',
        'ZY'    => 'INACTIVE OR DORMANT ACCOUNT (BENEFICIARY)',
        'XI'    => 'ACCOUNT DOES NOT EXIST (BENEFICIARY)',
        'Z5'    => 'INVALID BENEFICIARY CREDENTIALS',
        'YF'    => 'BENEFICIARY ACCOUNT BLOCKED/FROZEN',
        'Y1'    => 'BENEFICIARY CBS OFFLINE',
        'UB'    => 'UNABLE TO PROCESS DUE TO INTERNAL EXCEPTION AT SERVER/CBS/ETC ON BENEFICIARY SIDE',
        'XW'    => 'TRANSACTION CANNOT BE COMPLETED. COMPLIANCE VIOLATION (BENEFICIARY)',
        'XQ'    => 'TRANSACTION NOT PERMITTED TO CARDHOLDER (BENEFICIARY)',
        'XM'    => 'EXPIRED CARD, DECLINE (BENEFICIARY)',
        'XB'    => 'INVALID TRANSACTION OR IF MEMBER IS NOT ABLE TO FIND ANY APPROPRIATE RESPONSE CODE (REMITTER)',
        'RB'    => 'CREDIT REVERSAL TIMEOUT(REVERSAL)',
        'ZD'    => 'VALIDATION ERROR',
        'NO'    => 'NO ORIGINAL REQUEST FOUND DURING DEBIT/CREDIT',
        'B3'    => 'TRANSACTION NOT PERMITTED TO THE ACCOUNT',
        'Z9'    => 'INSUFFICIENT FUNDS IN CUSTOMER (REMITTER) ACCOUNT',
        'ZI'    => 'SUSPECTED FRAUD, DECLINE / TRANSACTIONS DECLINED BASED ON RISK SCORE BY BENEFICIARY',
        'XU'    => 'CUT-OFF IS IN PROCESS (BENEFICIARY)',
        'LC'    => 'UNABLE TO PROCESS CREDIT FROM BANK\'S POOL/BGL ACCOUNT',
        'DF'    => 'DUPLICATE RRN FOUND IN THE TRANSACTION. (BENEFICIARY)',
        'YD'    => 'DO NOT HONOUR (BENEFICIARY)',
        'K1'    => 'SUSPECTED FRAUD, DECLINE / TRANSACTIONS DECLINED BASED ON RISK SCORE BY REMITTER',
        'NA'    => 'TRANSACTION FAILED',
        'RNF'   => 'TRANSACTION FAILED',
        '51'    => 'NOT SUFFICIENT FUNDS',
        '96'    => 'Reversal Failure',
        'AM'    => 'MPIN not set by customer',
        'B1'    => 'Registered Mobile number linked to the account has been changed/removed',
        'UT'    => 'REMITTER/ISSUER UNAVAILABLE (TIMEOUT)',
        'UX'    => 'EXPIRED VIRTUAL ADDRESS',
        'XH'    => 'ACCOUNT DOES NOT EXIST (REMITTER)',
        'XV'    => 'TRANSACTION CANNOT BE COMPLETED. COMPLIANCE VIOLATION (REMITTER)',
        'Z6'    => 'No of PIN tries exceeded',
        'Z7'    => 'TRANSACTION FREQUENCY LIMIT EXCEEDED AS SET BY REMITTING MEMBER',
        'Z8'    => 'PER TRANSACTION LIMIT EXCEEDED AS SET BY REMITTING MEMBER',
        'ZA'    => 'TRANSACTION DECLINED BY CUSTOMER',
        'ZE'    => 'TRANSACTION NOT PERMITTED TO VPA by the PSP',
        'ZG'    => 'VPA RESTRICTED BY CUSTOMER',
        'ZH'    => 'INVALID VIRTUAL ADDRESS',
        'ZM'    => 'Invalid / Incorrect MPIN',
        'ZX'    => 'INACTIVE OR DORMANT ACCOUNT (REMITTER)',
        'U03'   => 'Net debit CAP is exceeded',
        'U09'   => 'ReqAuth Time out for PAY',
        'U14'   => 'Encryption error',
        'U16'   => 'Risk threshold exceeded',
        'U17'   => 'PSP is not registered',
        'U18'   => 'Request authorisation acknowledgement is not received',
        'U19'   => 'Request authorisation is declined',
        'U29'   => 'Address resolution is failed',
        'U30'   => 'Debit has been failed',
        'U53'   => 'PSP Request Pay Debit Acknowledgement not received',
        'U54'   => 'Transaction Id or Amount in credential block does not match with that in ReqPay',
        'U66'   => 'Device Fingerprint mismatch',
        'U67'   => 'Debit TimeOut',
        'U69'   => 'Collect Expired',
    ];

    protected static $errorCodeMap = [
        // Error Codes
        'U28'   => ErrorCode::GATEWAY_ERROR_PSP_NOT_AVAILABLE,
        'U88'   => ErrorCode::GATEWAY_ERROR_CONNECTION_TIMEOUT_IN_REQPAY_CREDIT,
        'U68'   => ErrorCode::GATEWAY_ERROR_CREDIT_TIMEOUT,
        'U31'   => ErrorCode::GATEWAY_ERROR_CREDIT_FAILED,
        // Response codes
        'BT'    => ErrorCode::GATEWAY_ERROR_TRANSACTION_PENDING,
        'XY'    => ErrorCode::GATEWAY_ERROR_REMITTER_CBS_OFFLINE,
        'XC'    => ErrorCode::GATEWAY_ERROR_INVALID_TRANSACTION_INAPPROPRIATE_CODE_BENEFICIARY,
        'ZY'    => ErrorCode::GATEWAY_ERROR_INACTIVE_DORMANT_BENEFICIARY_ACCOUNT,
        'XI'    => ErrorCode::GATEWAY_ERROR_BENEFICIARY_ACCOUNT_DOES_NOT_EXIST,
        'Z5'    => ErrorCode::GATEWAY_ERROR_INVALID_BENEFICIARY_CREDENTIALS,
        'YF'    => ErrorCode::GATEWAY_ERROR_BENEFICIARY_ACCOUNT_BLOCKED,
        'Y1'    => ErrorCode::GATEWAY_ERROR_BENEFICIARY_CBS_OFFLINE,
        'UB'    => ErrorCode::GATEWAY_ERROR_BENEFICIARY_INTERNAL_EXCEPTION,
        'XW'    => ErrorCode::GATEWAY_ERROR_BENEFICIARY_COMPLIANCE_VIOLATION,
        'XQ'    => ErrorCode::GATEWAY_ERROR_BENEFICIARY_TRANSACTION_NOT_PERMITTED,
        'XM'    => ErrorCode::GATEWAY_ERROR_BENEFICIARY_EXPIRED_CARD,
        'XB'    => ErrorCode::GATEWAY_ERROR_INVALID_TRANSACTION_INAPPROPRIATE_CODE_REMITTER,
        'RB'    => ErrorCode::GATEWAY_ERROR_CREDIT_REVERSAL_TIMEOUT,
        'ZD'    => ErrorCode::GATEWAY_ERROR_VALIDATION_ERROR,
        'NO'    => ErrorCode::GATEWAY_ERROR_NO_ORIGINAL_DEBIT_CREDIT_REQUEST_FOUND,
        'B3'    => ErrorCode::GATEWAY_ERROR_TRANSACTION_NOT_PERMITTED,
        'Z9'    => ErrorCode::GATEWAY_ERROR_INSUFFICIENT_FUNDS_REMITTER_ACCOUNT,
        'ZI'    => ErrorCode::GATEWAY_ERROR_PAYMENT_DECLINED_BY_BANK_DUE_TO_RISK_BENEFICARY,
        'XU'    => ErrorCode::GATEWAY_ERROR_BENEFICIARY_CUTOFF_IN_PROGRESS,
        'LC'    => ErrorCode::GATEWAY_ERROR_BANK_ACCOUNT_CREDIT_PROCESS_FAILED,
        'DF'    => ErrorCode::GATEWAY_ERROR_BENEFICIARY_DUPLICATE_RRN_FOUND,
        'YD'    => ErrorCode::GATEWAY_ERROR_DO_NOT_HONOUR_BENEFICIARY,
        'K1'    => ErrorCode::GATEWAY_ERROR_PAYMENT_DECLINED_BY_BANK_DUE_TO_RISK_REMITTER,
        'NA'    => ErrorCode::BAD_REQUEST_REFUND_FAILED,
        'RNF'   => ErrorCode::BAD_REQUEST_REFUND_FAILED,
        '51'    => ErrorCode::BAD_REQUEST_PAYMENT_ACCOUNT_INSUFFICIENT_BALANCE,
        '96'    => ErrorCode::GATEWAY_ERROR_REVERSAL_FAILURE,
        'AM'    => ErrorCode::BAD_REQUEST_UPI_MPIN_NOT_SET,
        'B1'    => ErrorCode::BAD_REQUEST_REGISTERED_MOBILE_NUMBER_NOT_FOUND,
        'UT'    => ErrorCode::GATEWAY_ERROR_PSP_NOT_AVAILABLE,
        'UX'    => ErrorCode::BAD_REQUEST_EXPIRED_VPA,
        'XH'    => ErrorCode::BAD_REQUEST_UPI_INVALID_BANK_ACCOUNT,
        'XV'    => ErrorCode::GATEWAY_ERROR_REMITTER_COMPLIANCE_VIOLATION,
        'Z6'    => ErrorCode::BAD_REQUEST_PAYMENT_PIN_ATTEMPTS_EXCEEDED,
        'Z7'    => ErrorCode::BAD_REQUEST_TRANSACTION_FREQUENCY_LIMIT_EXCEEDED,
        'Z8'    => ErrorCode::BAD_REQUEST_TRANSACTION_AMOUNT_LIMIT_EXCEEDED,
        'ZA'    => ErrorCode::BAD_REQUEST_PAYMENT_UPI_COLLECT_REQUEST_REJECTED,
        'ZE'    => ErrorCode::BAD_REQUEST_FORBIDDEN_TRANSACTION_ON_VPA,
        'ZG'    => ErrorCode::BAD_REQUEST_PAYMENT_UPI_RESTRICTED_VPA,
        'ZH'    => ErrorCode::BAD_REQUEST_PAYMENT_UPI_INVALID_VPA,
        'ZM'    => ErrorCode::BAD_REQUEST_PAYMENT_PIN_INCORRECT,
        'ZX'    => ErrorCode::BAD_REQUEST_UPI_INVALID_BANK_ACCOUNT,
        'U03'   => ErrorCode::BAD_REQUEST_TRANSACTION_AMOUNT_LIMIT_EXCEEDED,
        'U09'   => ErrorCode::GATEWAY_ERROR_UPI_REQAUTH_TIMEOUT,
        'U14'   => ErrorCode::GATEWAY_ERROR_ENCRYPTION_ERROR,
        'U16'   => ErrorCode::GATEWAY_ERROR_DENIED_BY_RISK,
        'U17'   => ErrorCode::BAD_REQUEST_PSP_DOESNT_EXIST,
        'U18'   => ErrorCode::GATEWAY_ERROR_UPI_REQAUTH_TIMEOUT,
        'U19'   => ErrorCode::GATEWAY_ERROR_REQAUTH_DECLINED,
        'U29'   => ErrorCode::GATEWAY_ERROR_VPA_RESOLUTION_FAILED,
        'U30'   => ErrorCode::GATEWAY_ERROR_DEBIT_FAILED,
        'U53'   => ErrorCode::GATEWAY_ERROR_DEBIT_FAILED_AT_BANK,
        'U54'   => ErrorCode::GATEWAY_ERROR_TRANSACTION_DETAILS_MISMATCH,
        'U66'   => ErrorCode::BAD_REQUEST_UPI_INVALID_DEVICE_FINGERPRINT,
        'U67'   => ErrorCode::GATEWAY_ERROR_DEBIT_TIMEOUT,
        'U69'   => ErrorCode::BAD_REQUEST_PAYMENT_UPI_COLLECT_REQUEST_EXPIRED,
    ];

    public static function getApiErrorCode($code = null, $action = Action::REFUND)
    {
        if ($action === Action::CALLBACK)
        {
            if ($code === 'NA')
            {
                return ErrorCode::BAD_REQUEST_PAYMENT_FAILED;
            }

            if ($code === 'RNF')
            {
                return ErrorCode::GATEWAY_ERROR_PAYMENT_NOT_FOUND;
            }
        }

        if (isset(self::$errorCodeMap[$code]))
        {
            return self::$errorCodeMap[$code];
        }

        return ErrorCode::GATEWAY_ERROR_FATAL_ERROR;
    }

    public static function getResponseCodeMessage($code)
    {
        return self::$errorCodes[$code] ?? 'Unknown Gateway Response Code';
    }
}
