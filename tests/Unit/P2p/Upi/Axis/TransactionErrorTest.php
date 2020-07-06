<?php

namespace RZP\Tests\Unit\P2p\Upi\Axis;

use RZP\Error\P2p\Error;
use RZP\Error\P2p\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Gateway\P2p\Upi\ErrorCodes;
use RZP\Gateway\P2p\Upi\Axis\ErrorMap;
use RZP\Models\P2p\Transaction\Status;
use RZP\Error\P2p\PublicErrorDescription;
use RZP\Tests\P2p\Service\UpiAxis\TestCase;
use RZP\Models\P2p\Transaction\UpiTransaction;
use RZP\Gateway\P2p\Upi\Axis\Transformers\TransactionTransformer;

class TransactionErrorTest extends TestCase
{
    public function errorCodeMapping()
    {
        return [
            'U28'   => [
                'U28',
                Status::FAILED,
                ErrorCode::GATEWAY_ERROR_PSP_NOT_AVAILABLE,
                PublicErrorCode::GATEWAY_ERROR,
                PublicErrorDescription::GATEWAY_ERROR_PSP_NOT_AVAILABLE,
            ],
            'U88'   => [
                'U88',
                Status::FAILED,
                ErrorCode::GATEWAY_ERROR_CONNECTION_TIMEOUT_IN_REQPAY_CREDIT,
                PublicErrorCode::GATEWAY_ERROR,
                PublicErrorDescription::GATEWAY_ERROR_CONNECTION_TIMEOUT_IN_REQPAY_CREDIT,
            ],
            'U68'   => [
                'U68',
                Status::FAILED,
                ErrorCode::GATEWAY_ERROR_CREDIT_TIMEOUT,
                PublicErrorCode::GATEWAY_ERROR,
                PublicErrorDescription::GATEWAY_ERROR_CREDIT_TIMEOUT,
            ],
            'U31'   => [
                'U31',
                Status::FAILED,
                ErrorCode::GATEWAY_ERROR_CREDIT_FAILED,
                PublicErrorCode::GATEWAY_ERROR,
                PublicErrorDescription::GATEWAY_ERROR_CREDIT_FAILED,
            ],
            'BT'    => [
                'BT',
                Status::PENDING,
                null,
                PublicErrorCode::GATEWAY_ERROR,
                PublicErrorDescription::GATEWAY_ERROR_TRANSACTION_PENDING,
            ],
            'XY'    => [
                'XY',
                Status::FAILED,
                ErrorCode::GATEWAY_ERROR_REMITTER_CBS_OFFLINE,
                PublicErrorCode::GATEWAY_ERROR,
                PublicErrorDescription::GATEWAY_ERROR_REMITTER_CBS_OFFLINE,
            ],
            'XC'    => [
                'XC',
                Status::FAILED,
                ErrorCode::GATEWAY_ERROR_INVALID_TRANSACTION_INAPPROPRIATE_CODE_BENEFICIARY,
                PublicErrorCode::GATEWAY_ERROR,
                PublicErrorDescription::GATEWAY_ERROR_INVALID_TRANSACTION_INAPPROPRIATE_CODE_BENEFICIARY,
            ],
            'ZY'    => [
                'ZY',
                Status::FAILED,
                ErrorCode::GATEWAY_ERROR_INACTIVE_DORMANT_BENEFICIARY_ACCOUNT,
                PublicErrorCode::GATEWAY_ERROR,
                PublicErrorDescription::GATEWAY_ERROR_INACTIVE_DORMANT_BENEFICIARY_ACCOUNT,
            ],
            'XI'    => [
                'XI',
                Status::FAILED,
                ErrorCode::GATEWAY_ERROR_BENEFICIARY_ACCOUNT_DOES_NOT_EXIST,
                PublicErrorCode::GATEWAY_ERROR,
                PublicErrorDescription::GATEWAY_ERROR_BENEFICIARY_ACCOUNT_DOES_NOT_EXIST,
            ],
            'Z5'    => [
                'Z5',
                Status::FAILED,
                ErrorCode::GATEWAY_ERROR_INVALID_BENEFICIARY_CREDENTIALS,
                PublicErrorCode::GATEWAY_ERROR,
                PublicErrorDescription::GATEWAY_ERROR_INVALID_BENEFICIARY_CREDENTIALS,
            ],
            'YF'    => [
                'YF',
                Status::FAILED,
                ErrorCode::GATEWAY_ERROR_BENEFICIARY_ACCOUNT_BLOCKED,
                PublicErrorCode::GATEWAY_ERROR,
                PublicErrorDescription::GATEWAY_ERROR_BENEFICIARY_ACCOUNT_BLOCKED,
            ],
            'Y1'    => [
                'Y1',
                Status::FAILED,
                ErrorCode::GATEWAY_ERROR_BENEFICIARY_CBS_OFFLINE,
                PublicErrorCode::GATEWAY_ERROR,
                PublicErrorDescription::GATEWAY_ERROR_BENEFICIARY_CBS_OFFLINE,
            ],
            'UB'    => [
                'UB',
                Status::FAILED,
                ErrorCode::GATEWAY_ERROR_BENEFICIARY_INTERNAL_EXCEPTION,
                PublicErrorCode::GATEWAY_ERROR,
                PublicErrorDescription::GATEWAY_ERROR,
            ],
            'XW'    => [
                'XW',
                Status::FAILED,
                ErrorCode::GATEWAY_ERROR_BENEFICIARY_COMPLIANCE_VIOLATION,
                PublicErrorCode::GATEWAY_ERROR,
                PublicErrorDescription::GATEWAY_ERROR_BENEFICIARY_COMPLIANCE_VIOLATION,
            ],
            'XQ'    => [
                'XQ',
                Status::FAILED,
                ErrorCode::GATEWAY_ERROR_BENEFICIARY_TRANSACTION_NOT_PERMITTED,
                PublicErrorCode::GATEWAY_ERROR,
                PublicErrorDescription::GATEWAY_ERROR_BENEFICIARY_TRANSACTION_NOT_PERMITTED,
            ],
            'XM'    => [
                'XM',
                Status::FAILED,
                ErrorCode::GATEWAY_ERROR_BENEFICIARY_EXPIRED_CARD,
                PublicErrorCode::GATEWAY_ERROR,
                PublicErrorDescription::GATEWAY_ERROR_BENEFICIARY_EXPIRED_CARD,
            ],
            'XB'    => [
                'XB',
                Status::FAILED,
                ErrorCode::GATEWAY_ERROR_INVALID_TRANSACTION_INAPPROPRIATE_CODE_REMITTER,
                PublicErrorCode::GATEWAY_ERROR,
                PublicErrorDescription::GATEWAY_ERROR_INVALID_TRANSACTION_INAPPROPRIATE_CODE_REMITTER,
            ],
            'RB'    => [
                'RB',
                Status::PENDING,
                null,
                PublicErrorCode::GATEWAY_ERROR,
                PublicErrorDescription::GATEWAY_ERROR_TRANSACTION_PENDING,
            ],
            'ZD'    => [
                'ZD',
                Status::FAILED,
                ErrorCode::GATEWAY_ERROR_VALIDATION_ERROR,
                PublicErrorCode::GATEWAY_ERROR,
                PublicErrorDescription::GATEWAY_ERROR_VALIDATION_ERROR,
            ],
            'NO'    => [
                'NO',
                Status::FAILED,
                ErrorCode::GATEWAY_ERROR_NO_ORIGINAL_DEBIT_CREDIT_REQUEST_FOUND,
                PublicErrorCode::GATEWAY_ERROR,
                PublicErrorDescription::GATEWAY_ERROR_NO_ORIGINAL_DEBIT_CREDIT_REQUEST_FOUND,
            ],
            'B3'    => [
                'B3',
                Status::FAILED,
                ErrorCode::GATEWAY_ERROR_TRANSACTION_NOT_PERMITTED,
                PublicErrorCode::GATEWAY_ERROR,
                PublicErrorDescription::GATEWAY_ERROR_TRANSACTION_NOT_PERMITTED,
            ],
            'Z9'    => [
                'Z9',
                Status::FAILED,
                ErrorCode::GATEWAY_ERROR_INSUFFICIENT_FUNDS_REMITTER_ACCOUNT,
                PublicErrorCode::GATEWAY_ERROR,
                PublicErrorDescription::GATEWAY_ERROR_INSUFFICIENT_FUNDS_REMITTER_ACCOUNT,
            ],
            'ZI'    => [
                'ZI',
                Status::FAILED,
                ErrorCode::GATEWAY_ERROR_PAYMENT_DECLINED_BY_BANK_DUE_TO_RISK_BENEFICARY,
                PublicErrorCode::GATEWAY_ERROR,
                PublicErrorDescription::GATEWAY_ERROR_PAYMENT_DECLINED_BY_BANK_DUE_TO_RISK_BENEFICARY,
            ],
            'XU'    => [
                'XU',
                Status::FAILED,
                ErrorCode::GATEWAY_ERROR_BENEFICIARY_CUTOFF_IN_PROGRESS,
                PublicErrorCode::GATEWAY_ERROR,
                PublicErrorDescription::GATEWAY_ERROR_BENEFICIARY_CUTOFF_IN_PROGRESS,
            ],
            'LC'    => [
                'LC',
                Status::FAILED,
                ErrorCode::GATEWAY_ERROR_BANK_ACCOUNT_CREDIT_PROCESS_FAILED,
                PublicErrorCode::GATEWAY_ERROR,
                PublicErrorDescription::GATEWAY_ERROR_BANK_ACCOUNT_CREDIT_PROCESS_FAILED,
            ],
            'DF'    => [
                'DF',
                Status::FAILED,
                ErrorCode::GATEWAY_ERROR_BENEFICIARY_DUPLICATE_RRN_FOUND,
                PublicErrorCode::GATEWAY_ERROR,
                PublicErrorDescription::GATEWAY_ERROR_BENEFICIARY_DUPLICATE_RRN_FOUND,
            ],
            'YD'    => [
                'YD',
                Status::FAILED,
                ErrorCode::GATEWAY_ERROR_DO_NOT_HONOUR_BENEFICIARY,
                PublicErrorCode::GATEWAY_ERROR,
                PublicErrorDescription::GATEWAY_ERROR_DO_NOT_HONOUR_BENEFICIARY,
            ],
            'K1'    => [
                'K1',
                Status::FAILED,
                ErrorCode::GATEWAY_ERROR_PAYMENT_DECLINED_BY_BANK_DUE_TO_RISK_REMITTER,
                PublicErrorCode::GATEWAY_ERROR,
                PublicErrorDescription::GATEWAY_ERROR_PAYMENT_DECLINED_BY_BANK_DUE_TO_RISK_REMITTER,
            ],
            'NA'    => [
                'NA',
                Status::FAILED,
                ErrorCode::BAD_REQUEST_REFUND_FAILED,
                PublicErrorCode::BAD_REQUEST_ERROR,
                PublicErrorDescription::BAD_REQUEST_REFUND_FAILED,
            ],
            'RNF'   => [
                'RNF',
                Status::FAILED,
                ErrorCode::BAD_REQUEST_REFUND_FAILED,
                PublicErrorCode::BAD_REQUEST_ERROR,
                PublicErrorDescription::BAD_REQUEST_REFUND_FAILED,
            ],
            '51'    => [
                '51',
                Status::FAILED,
                ErrorCode::BAD_REQUEST_PAYMENT_ACCOUNT_INSUFFICIENT_BALANCE,
                PublicErrorCode::BAD_REQUEST_ERROR,
                PublicErrorDescription::BAD_REQUEST_PAYMENT_ACCOUNT_INSUFFICIENT_BALANCE,
            ],
            '96'    => [
                '96',
                Status::FAILED,
                ErrorCode::GATEWAY_ERROR_REVERSAL_FAILURE,
                PublicErrorCode::GATEWAY_ERROR,
                PublicErrorDescription::GATEWAY_ERROR,
            ],
            'AM'    => [
                'AM',
                Status::FAILED,
                ErrorCode::BAD_REQUEST_UPI_MPIN_NOT_SET,
                PublicErrorCode::BAD_REQUEST_ERROR,
                PublicErrorDescription::BAD_REQUEST_UPI_MPIN_NOT_SET,
            ],
            'B1'    => [
                'B1',
                Status::FAILED,
                ErrorCode::BAD_REQUEST_REGISTERED_MOBILE_NUMBER_NOT_FOUND,
                PublicErrorCode::BAD_REQUEST_ERROR,
                PublicErrorDescription::BAD_REQUEST_REGISTERED_MOBILE_NUMBER_NOT_FOUND,
            ],
            'UT'    => [
                'UT',
                Status::FAILED,
                ErrorCode::GATEWAY_ERROR_PSP_NOT_AVAILABLE,
                PublicErrorCode::GATEWAY_ERROR,
                PublicErrorDescription::GATEWAY_ERROR_PSP_NOT_AVAILABLE,
            ],
            'UX'    => [
                'UX',
                Status::FAILED,
                ErrorCode::BAD_REQUEST_EXPIRED_VPA,
                PublicErrorCode::BAD_REQUEST_ERROR,
                PublicErrorDescription::BAD_REQUEST_EXPIRED_VPA,
            ],
            'XH'    => [
                'XH',
                Status::FAILED,
                ErrorCode::BAD_REQUEST_UPI_INVALID_BANK_ACCOUNT,
                PublicErrorCode::BAD_REQUEST_ERROR,
                PublicErrorDescription::BAD_REQUEST_UPI_INVALID_BANK_ACCOUNT,
            ],
            'XV'    => [
                'XV',
                Status::FAILED,
                ErrorCode::GATEWAY_ERROR_REMITTER_COMPLIANCE_VIOLATION,
                PublicErrorCode::GATEWAY_ERROR,
                PublicErrorDescription::GATEWAY_ERROR_REMITTER_COMPLIANCE_VIOLATION,
            ],
            'Z6'    => [
                'Z6',
                Status::FAILED,
                ErrorCode::BAD_REQUEST_PAYMENT_PIN_ATTEMPTS_EXCEEDED,
                PublicErrorCode::BAD_REQUEST_ERROR,
                PublicErrorDescription::BAD_REQUEST_PAYMENT_PIN_ATTEMPTS_EXCEEDED,
            ],
            'Z7'    => [
                'Z7',
                Status::FAILED,
                ErrorCode::BAD_REQUEST_TRANSACTION_FREQUENCY_LIMIT_EXCEEDED,
                PublicErrorCode::BAD_REQUEST_ERROR,
                PublicErrorDescription::BAD_REQUEST_TRANSACTION_FREQUENCY_LIMIT_EXCEEDED,
            ],
            'Z8'    => [
                'Z8',
                Status::FAILED,
                ErrorCode::BAD_REQUEST_TRANSACTION_AMOUNT_LIMIT_EXCEEDED,
                PublicErrorCode::BAD_REQUEST_ERROR,
                PublicErrorDescription::BAD_REQUEST_TRANSACTION_AMOUNT_LIMIT_EXCEEDED,
            ],
            'ZA'    => [
                'ZA',
                Status::REJECTED,
                ErrorCode::BAD_REQUEST_PAYMENT_UPI_COLLECT_REQUEST_REJECTED,
                PublicErrorCode::BAD_REQUEST_ERROR,
                PublicErrorDescription::BAD_REQUEST_PAYMENT_UPI_COLLECT_REQUEST_REJECTED,
            ],
            'ZE'    => [
                'ZE',
                Status::FAILED,
                ErrorCode::BAD_REQUEST_FORBIDDEN_TRANSACTION_ON_VPA,
                PublicErrorCode::BAD_REQUEST_ERROR,
                PublicErrorDescription::BAD_REQUEST_FORBIDDEN_TRANSACTION_ON_VPA,
            ],
            'ZG'    => [
                'ZG',
                Status::FAILED,
                ErrorCode::BAD_REQUEST_PAYMENT_UPI_RESTRICTED_VPA,
                PublicErrorCode::BAD_REQUEST_ERROR,
                PublicErrorDescription::BAD_REQUEST_PAYMENT_UPI_RESTRICTED_VPA,
            ],
            'ZH'    => [
                'ZH',
                Status::FAILED,
                ErrorCode::BAD_REQUEST_PAYMENT_UPI_INVALID_VPA,
                PublicErrorCode::BAD_REQUEST_ERROR,
                PublicErrorDescription::BAD_REQUEST_PAYMENT_UPI_INVALID_VPA,
            ],
            'ZM'    => [
                'ZM',
                Status::FAILED,
                ErrorCode::BAD_REQUEST_PAYMENT_PIN_INCORRECT,
                PublicErrorCode::BAD_REQUEST_ERROR,
                PublicErrorDescription::BAD_REQUEST_PAYMENT_PIN_INCORRECT,
            ],
            'ZX'    => [
                'ZX',
                Status::FAILED,
                ErrorCode::BAD_REQUEST_UPI_INVALID_BANK_ACCOUNT,
                PublicErrorCode::BAD_REQUEST_ERROR,
                PublicErrorDescription::BAD_REQUEST_UPI_INVALID_BANK_ACCOUNT,
            ],
            'U03'   => [
                'U03',
                Status::FAILED,
                ErrorCode::BAD_REQUEST_TRANSACTION_AMOUNT_LIMIT_EXCEEDED,
                PublicErrorCode::BAD_REQUEST_ERROR,
                PublicErrorDescription::BAD_REQUEST_TRANSACTION_AMOUNT_LIMIT_EXCEEDED,
            ],
            'U09'   => [
                'U09',
                Status::FAILED,
                ErrorCode::GATEWAY_ERROR_UPI_REQAUTH_TIMEOUT,
                PublicErrorCode::GATEWAY_ERROR,
                PublicErrorDescription::GATEWAY_ERROR_UPI_REQAUTH_TIMEOUT,
            ],
            'U14'   => [
                'U14',
                Status::FAILED,
                ErrorCode::GATEWAY_ERROR_ENCRYPTION_ERROR,
                PublicErrorCode::GATEWAY_ERROR,
                PublicErrorDescription::GATEWAY_ERROR_ENCRYPTION_ERROR,
            ],
            'U16'   => [
                'U16',
                Status::FAILED,
                ErrorCode::GATEWAY_ERROR_DENIED_BY_RISK,
                PublicErrorCode::GATEWAY_ERROR,
                PublicErrorDescription::GATEWAY_ERROR_DENIED_BY_RISK,
            ],
            'U17'   => [
                'U17',
                Status::FAILED,
                ErrorCode::BAD_REQUEST_PSP_DOESNT_EXIST,
                PublicErrorCode::BAD_REQUEST_ERROR,
                PublicErrorDescription::BAD_REQUEST_PSP_DOESNT_EXIST,
            ],
            'U18'   => [
                'U18',
                Status::FAILED,
                ErrorCode::GATEWAY_ERROR_UPI_REQAUTH_TIMEOUT,
                PublicErrorCode::GATEWAY_ERROR,
                PublicErrorDescription::GATEWAY_ERROR_UPI_REQAUTH_TIMEOUT,
            ],
            'U19'   => [
                'U19',
                Status::FAILED,
                ErrorCode::GATEWAY_ERROR_REQAUTH_DECLINED,
                PublicErrorCode::GATEWAY_ERROR,
                PublicErrorDescription::GATEWAY_ERROR,
            ],
            'U29'   => [
                'U29',
                Status::FAILED,
                ErrorCode::GATEWAY_ERROR_VPA_RESOLUTION_FAILED,
                PublicErrorCode::GATEWAY_ERROR,
                PublicErrorDescription::GATEWAY_ERROR,
            ],
            'U30'   => [
                'U30',
                Status::FAILED,
                ErrorCode::GATEWAY_ERROR_DEBIT_FAILED,
                PublicErrorCode::GATEWAY_ERROR,
                PublicErrorDescription::GATEWAY_ERROR_DEBIT_FAILED,
            ],
            'U53'   => [
                'U53',
                Status::FAILED,
                ErrorCode::GATEWAY_ERROR_DEBIT_FAILED_AT_BANK,
                PublicErrorCode::GATEWAY_ERROR,
                PublicErrorDescription::GATEWAY_ERROR,
            ],
            'U54'   => [
                'U54',
                Status::FAILED,
                ErrorCode::GATEWAY_ERROR_TRANSACTION_DETAILS_MISMATCH,
                PublicErrorCode::GATEWAY_ERROR,
                PublicErrorDescription::GATEWAY_ERROR_TRANSACTION_DETAILS_MISMATCH,
            ],
            'U66'   => [
                'U66',
                Status::FAILED,
                ErrorCode::BAD_REQUEST_UPI_INVALID_DEVICE_FINGERPRINT,
                PublicErrorCode::BAD_REQUEST_ERROR,
                PublicErrorDescription::BAD_REQUEST_UPI_INVALID_DEVICE_FINGERPRINT,
            ],
            'U67'   => [
                'U67',
                Status::FAILED,
                ErrorCode::GATEWAY_ERROR_DEBIT_TIMEOUT,
                PublicErrorCode::GATEWAY_ERROR,
                PublicErrorDescription::GATEWAY_ERROR_DEBIT_TIMEOUT,
            ],
            'U69'   => [
                'U69',
                Status::EXPIRED,
                ErrorCode::BAD_REQUEST_PAYMENT_UPI_COLLECT_REQUEST_EXPIRED,
                PublicErrorCode::BAD_REQUEST_ERROR,
                PublicErrorDescription::BAD_REQUEST_PAYMENT_UPI_COLLECT_REQUEST_EXPIRED,
            ],
            '61'    => [
                '61',
                Status::FAILED,
                ErrorCode::BAD_REQUEST_TRANSACTION_AMOUNT_LIMIT_EXCEEDED,
                PublicErrorCode::BAD_REQUEST_ERROR,
                PublicErrorDescription::BAD_REQUEST_TRANSACTION_AMOUNT_LIMIT_EXCEEDED,
            ],
            'A01'   => [
                'A01',
                Status::FAILED,
                ErrorCode::BAD_REQUEST_UPI_INVALID_BANK_ACCOUNT,
                PublicErrorCode::BAD_REQUEST_ERROR,
                PublicErrorDescription::BAD_REQUEST_UPI_INVALID_BANK_ACCOUNT,
            ],
            'B07'   => [
                'B07',
                Status::FAILED,
                ErrorCode::GATEWAY_ERROR_INTERNAL_FORMAT_ERROR,
                PublicErrorCode::GATEWAY_ERROR,
                PublicErrorDescription::GATEWAY_ERROR_INTERNAL_FORMAT_ERROR,
            ],
            'B2'    => [
                'B2',
                Status::FAILED,
                ErrorCode::BAD_REQUEST_PAYMENT_UPI_MULTIPLE_ACCOUNTS_LINKED,
                PublicErrorCode::BAD_REQUEST_ERROR,
                PublicErrorDescription::BAD_REQUEST_PAYMENT_UPI_MULTIPLE_ACCOUNTS_LINKED,
            ],
            'HS'    => [
                'HS',
                Status::FAILED,
                ErrorCode::GATEWAY_ERROR_DEBIT_FAILED_AT_BANK,
                PublicErrorCode::GATEWAY_ERROR,
                PublicErrorDescription::GATEWAY_ERROR,
            ],
            'I01'   => [
                'I01',
                Status::FAILED,
                ErrorCode::GATEWAY_ERROR_INTERNAL_FORMAT_ERROR,
                PublicErrorCode::GATEWAY_ERROR,
                PublicErrorDescription::GATEWAY_ERROR_INTERNAL_FORMAT_ERROR,
            ],
            'IR'    => [
                'IR',
                Status::FAILED,
                ErrorCode::GATEWAY_ERROR_FATAL_ERROR,
                PublicErrorCode::GATEWAY_ERROR,
                PublicErrorDescription::GATEWAY_ERROR_FATAL_ERROR,
            ],
            'LD'    => [
                'LD',
                Status::FAILED,
                ErrorCode::GATEWAY_ERROR_DEBIT_FAILED,
                PublicErrorCode::GATEWAY_ERROR,
                PublicErrorDescription::GATEWAY_ERROR_DEBIT_FAILED,
            ],
            'R02'   => [
                'R02',
                Status::FAILED,
                ErrorCode::BAD_REQUEST_PAYMENT_UPI_INVALID_VPA,
                PublicErrorCode::BAD_REQUEST_ERROR,
                PublicErrorDescription::BAD_REQUEST_PAYMENT_UPI_INVALID_VPA,
            ],
            'RM'    => [
                'RM',
                Status::FAILED,
                ErrorCode::BAD_REQUEST_UPI_MPIN_NOT_SET,
                PublicErrorCode::BAD_REQUEST_ERROR,
                PublicErrorDescription::BAD_REQUEST_UPI_MPIN_NOT_SET,
            ],
            'TM'    => [
                'TM',
                Status::FAILED,
                ErrorCode::BAD_REQUEST_UPI_INVALID_ATM_PIN,
                PublicErrorCode::BAD_REQUEST_ERROR,
                PublicErrorDescription::BAD_REQUEST_UPI_INVALID_ATM_PIN,
            ],
            'U10'   => [
                'U10',
                Status::FAILED,
                ErrorCode::BAD_REQUEST_FORBIDDEN,
                PublicErrorCode::BAD_REQUEST_ERROR,
                PublicErrorDescription::BAD_REQUEST_FORBIDDEN,
            ],
            'U26'   => [
                'U26',
                Status::FAILED,
                ErrorCode::GATEWAY_ERROR_CONNECTION_TIMEOUT_IN_REQPAY_CREDIT,
                PublicErrorCode::GATEWAY_ERROR,
                PublicErrorDescription::GATEWAY_ERROR_CONNECTION_TIMEOUT_IN_REQPAY_CREDIT,
            ],
            'U85'   => [
                'U85',
                Status::FAILED,
                ErrorCode::GATEWAY_ERROR_CONNECTION_TIMEOUT_IN_REQPAY_DEBIT,
                PublicErrorCode::GATEWAY_ERROR,
                PublicErrorDescription::GATEWAY_ERROR_CONNECTION_TIMEOUT_IN_REQPAY_DEBIT,
            ],
            'XF'    => [
                'XF',
                Status::FAILED,
                ErrorCode::GATEWAY_ERROR_INTERNAL_FORMAT_ERROR,
                PublicErrorCode::GATEWAY_ERROR,
                PublicErrorDescription::GATEWAY_ERROR_INTERNAL_FORMAT_ERROR,
            ],
            'XJ'    => [
                'XJ',
                Status::FAILED,
                ErrorCode::BAD_REQUEST_PAYMENT_UPI_FUNCTION_NOT_SUPPORTED,
                PublicErrorCode::BAD_REQUEST_ERROR,
                PublicErrorDescription::BAD_REQUEST_PAYMENT_UPI_FUNCTION_NOT_SUPPORTED,
            ],
            'XN'    => [
                'XN',
                Status::FAILED,
                ErrorCode::BAD_REQUEST_INVALID_CARD_DETAILS,
                PublicErrorCode::BAD_REQUEST_ERROR,
                PublicErrorDescription::BAD_REQUEST_INVALID_CARD_DETAILS,
            ],
            'XP'    => [
                'XP',
                Status::FAILED,
                ErrorCode::GATEWAY_ERROR_TRANSACTION_NOT_PERMITTED,
                PublicErrorCode::GATEWAY_ERROR,
                PublicErrorDescription::GATEWAY_ERROR_TRANSACTION_NOT_PERMITTED,
            ],
            'XT'    => [
                'XT',
                Status::FAILED,
                ErrorCode::GATEWAY_ERROR_BENEFICIARY_CUTOFF_IN_PROGRESS,
                PublicErrorCode::GATEWAY_ERROR,
                PublicErrorDescription::GATEWAY_ERROR_BENEFICIARY_CUTOFF_IN_PROGRESS,
            ],
            'YE'    => [
                'YE',
                Status::FAILED,
                ErrorCode::BAD_REQUEST_ACCOUNT_BLOCKED,
                PublicErrorCode::BAD_REQUEST_ERROR,
                PublicErrorDescription::BAD_REQUEST_ACCOUNT_BLOCKED,
            ],
            'DT'    => [
                'DT',
                Status::FAILED,
                ErrorCode::GATEWAY_ERROR_REMITTER_DUPLICATE_RRN_FOUND,
                PublicErrorCode::GATEWAY_ERROR,
                PublicErrorDescription::GATEWAY_ERROR,
            ],
            '91'    => [
                '91',
                Status::FAILED,
                ErrorCode::GATEWAY_ERROR_NPCI_RESPONSE_TIMEOUT,
                PublicErrorCode::GATEWAY_ERROR,
                PublicErrorDescription::GATEWAY_ERROR,
            ],
            'YC'    => [
                'YC',
                Status::FAILED,
                ErrorCode::GATEWAY_ERROR_DO_NOT_HONOUR_REMITTER,
                PublicErrorCode::GATEWAY_ERROR,
                PublicErrorDescription::GATEWAY_ERROR_DO_NOT_HONOUR_REMITTER,
            ],
            'YG'    => [
                'YG',
                Status::FAILED,
                ErrorCode::BAD_REQUEST_PSP_ERROR,
                PublicErrorCode::BAD_REQUEST_ERROR,
                PublicErrorDescription::BAD_REQUEST_PSP_ERROR,
            ],
            'XL'    => [
                'XL',
                Status::FAILED,
                ErrorCode::BAD_REQUEST_P2P_REGISTRATION_CARD_EXPIRED,
                PublicErrorCode::BAD_REQUEST_ERROR,
                PublicErrorDescription::BAD_REQUEST_P2P_REGISTRATION_CARD_EXPIRED,
            ],
            'D01'   => [
                'D01',
                Status::FAILED,
                ErrorCode::BAD_REQUEST_PAYMENT_UPI_DEVICE_MISSING,
                PublicErrorCode::BAD_REQUEST_ERROR,
                PublicErrorDescription::BAD_REQUEST_PAYMENT_UPI_DEVICE_MISSING,
            ],
            'X1'    => [
                'X1',
                Status::FAILED,
                ErrorCode::BAD_REQUEST_PAYMENT_UPI_COLLECT_REQUEST_EXPIRED,
                PublicErrorCode::BAD_REQUEST_ERROR,
                PublicErrorDescription::BAD_REQUEST_PAYMENT_UPI_COLLECT_REQUEST_EXPIRED,
            ],
            'BR'    => [
                'BR',
                Status::FAILED,
                ErrorCode::BAD_REQUEST_PAYMENT_UPI_MOBILE_NUMBER_MAPPED_TO_MULTIPLE_CUSTOMERS,
                PublicErrorCode::BAD_REQUEST_ERROR,
                PublicErrorDescription::BAD_REQUEST_PAYMENT_UPI_MOBILE_NUMBER_MAPPED_TO_MULTIPLE_CUSTOMERS,
            ],
            'B03'   => [
                'B03',
                Status::FAILED,
                ErrorCode::BAD_REQUEST_PAYMENT_UPI_INVALID_VPA,
                PublicErrorCode::BAD_REQUEST_ERROR,
                PublicErrorDescription::BAD_REQUEST_PAYMENT_UPI_INVALID_VPA,
            ],
            'XG'    => [
                'XG',
                Status::FAILED,
                ErrorCode::GATEWAY_ERROR_FORMATTING_FAILED,
                PublicErrorCode::GATEWAY_ERROR,
                PublicErrorDescription::GATEWAY_ERROR,
            ],
            'M5'    => [
                'M5',
                Status::FAILED,
                ErrorCode::BAD_REQUEST_ACCOUNT_CLOSED,
                PublicErrorCode::BAD_REQUEST_ERROR,
                PublicErrorDescription::BAD_REQUEST_ACCOUNT_CLOSED,
            ],
            'U01'   => [
                'U01',
                Status::FAILED,
                ErrorCode::BAD_REQUEST_DUPLICATE_REQUEST,
                PublicErrorCode::BAD_REQUEST_ERROR,
                PublicErrorDescription::BAD_REQUEST_DUPLICATE_REQUEST,
            ],
            'U21'   => [
                'U21',
                Status::FAILED,
                ErrorCode::BAD_REQUEST_UNAUTHORIZED,
                PublicErrorCode::BAD_REQUEST_ERROR,
                PublicErrorDescription::BAD_REQUEST_UNAUTHORIZED,
            ],
            '08'     => [
                '08',
                Status::FAILED,
                ErrorCode::GATEWAY_ERROR_INVALID_RESPONSE,
                PublicErrorCode::GATEWAY_ERROR,
                PublicErrorDescription::GATEWAY_ERROR,
            ],
            'XD'    => [
                'XD',
                Status::FAILED,
                ErrorCode::GATEWAY_ERROR_CARD_INVALID_AMOUNT,
                PublicErrorCode::GATEWAY_ERROR,
                PublicErrorDescription::GATEWAY_ERROR_CARD_INVALID_AMOUNT,
            ],
            'U96'   => [
                'U96',
                Status::FAILED,
                ErrorCode::BAD_REQUEST_PAYMENT_UPI_DEBIT_AND_CREDIT_SAME_ACCOUNT,
                PublicErrorCode::BAD_REQUEST_ERROR,
                PublicErrorDescription::BAD_REQUEST_PAYMENT_UPI_DEBIT_AND_CREDIT_SAME_ACCOUNT,
            ],
            'XK'    => [
                'XK',
                Status::FAILED,
                ErrorCode::BAD_REQUEST_PAYMENT_UPI_FUNCTION_NOT_SUPPORTED,
                PublicErrorCode::BAD_REQUEST_ERROR,
                PublicErrorDescription::BAD_REQUEST_PAYMENT_UPI_FUNCTION_NOT_SUPPORTED,
            ],
            'T03'   => [
                'T03',
                Status::FAILED,
                ErrorCode::GATEWAY_ERROR_INVALID_FORMAT,
                PublicErrorCode::GATEWAY_ERROR,
                PublicErrorDescription::GATEWAY_ERROR_INVALID_FORMAT,
            ],
            'L04'   => [
                'L04',
                Status::FAILED,
                ErrorCode::GATEWAY_ERROR_INVALID_FORMAT,
                PublicErrorCode::GATEWAY_ERROR,
                PublicErrorDescription::GATEWAY_ERROR_INVALID_FORMAT,
            ],
            'A09'   => [
                'A09',
                Status::FAILED,
                ErrorCode::GATEWAY_ERROR_INVALID_FORMAT,
                PublicErrorCode::GATEWAY_ERROR,
                PublicErrorDescription::GATEWAY_ERROR_INVALID_FORMAT,
            ],
            'U78'   => [
                'U78',
                Status::FAILED,
                ErrorCode::GATEWAY_ERROR_BENEFICIARY_CBS_OFFLINE,
                PublicErrorCode::GATEWAY_ERROR,
                PublicErrorDescription::GATEWAY_ERROR_BENEFICIARY_CBS_OFFLINE,
            ],
            'L05'   => [
                'L05',
                Status::FAILED,
                ErrorCode::GATEWAY_ERROR_TIMED_OUT,
                PublicErrorCode::GATEWAY_ERROR,
                PublicErrorDescription::GATEWAY_ERROR,
            ],
            'ZU'    => [
                'ZU',
                Status::FAILED,
                ErrorCode::GATEWAY_ERROR_TECHNICAL_ERROR,
                PublicErrorCode::GATEWAY_ERROR,
                PublicErrorDescription::GATEWAY_ERROR_TECHNICAL_ERROR,
            ],
            'IE'    => [
                'IE',
                Status::FAILED,
                ErrorCode::GATEWAY_ERROR_FUNDS_BLOCKED_BY_MANDATE,
                PublicErrorCode::GATEWAY_ERROR,
                PublicErrorDescription::GATEWAY_ERROR_FUNDS_BLOCKED_BY_MANDATE,
            ],
            'FL'    => [
                'FL',
                Status::FAILED,
                ErrorCode::GATEWAY_ERROR_UPI_COOLING_PERIOD_TRANSACTION_LIMIT_EXCEEDS,
                PublicErrorCode::GATEWAY_ERROR,
                PublicErrorDescription::GATEWAY_ERROR_UPI_COOLING_PERIOD_TRANSACTION_LIMIT_EXCEEDS,
            ],
            'FP'    => [
                'FP',
                Status::FAILED,
                ErrorCode::GATEWAY_ERROR_UPI_COOLING_PERIOD_TRANSACTION_LIMIT_EXCEEDS,
                PublicErrorCode::GATEWAY_ERROR,
                PublicErrorDescription::GATEWAY_ERROR_UPI_COOLING_PERIOD_TRANSACTION_LIMIT_EXCEEDS,
            ],
            'VM'    => [
                'VM',
                Status::FAILED,
                ErrorCode::GATEWAY_ERROR_DEBIT_NOT_ALLOWED_FOR_BANK,
                PublicErrorCode::GATEWAY_ERROR,
                PublicErrorDescription::GATEWAY_ERROR_DEBIT_NOT_ALLOWED_FOR_BANK,
            ],
            'ND'    => [
                'ND',
                Status::FAILED,
                ErrorCode::GATEWAY_ERROR_TECHNICAL_ERROR,
                PublicErrorCode::GATEWAY_ERROR,
                PublicErrorDescription::GATEWAY_ERROR_TECHNICAL_ERROR,
            ],
            'M2'    => [
                'M2',
                Status::FAILED,
                ErrorCode::GATEWAY_ERROR_UPI_PER_DAY_LIMIT_EXCEEDS,
                PublicErrorCode::GATEWAY_ERROR,
                PublicErrorDescription::GATEWAY_ERROR_UPI_PER_DAY_LIMIT_EXCEEDS,
            ],
            'OD'    => [
                'OD',
                Status::FAILED,
                ErrorCode::GATEWAY_ERROR_TECHNICAL_ERROR,
                PublicErrorCode::GATEWAY_ERROR,
                PublicErrorDescription::GATEWAY_ERROR_TECHNICAL_ERROR,
            ],
            'RR'    => [
                'RR',
                Status::FAILED,
                ErrorCode::GATEWAY_ERROR_TECHNICAL_ERROR,
                PublicErrorCode::GATEWAY_ERROR,
                PublicErrorDescription::GATEWAY_ERROR_TECHNICAL_ERROR,
            ],
            'U80'   => [
                'U80',
                Status::FAILED,
                ErrorCode::GATEWAY_ERROR_TECHNICAL_ERROR,
                PublicErrorCode::GATEWAY_ERROR,
                PublicErrorDescription::GATEWAY_ERROR_TECHNICAL_ERROR,
            ],
            'RA'    => [
                'RA',
                Status::FAILED,
                ErrorCode::BAD_REQUEST_PAYMENT_UPI_DEBIT_AND_CREDIT_SAME_ACCOUNT,
                PublicErrorCode::BAD_REQUEST_ERROR,
                PublicErrorDescription::BAD_REQUEST_PAYMENT_UPI_DEBIT_AND_CREDIT_SAME_ACCOUNT,
            ],
            'YA'    => [
                'YA',
                Status::FAILED,
                ErrorCode::BAD_REQUEST_PAYMENT_CARD_DETAILS_INVALID,
                PublicErrorCode::BAD_REQUEST_ERROR,
                PublicErrorDescription::BAD_REQUEST_PAYMENT_CARD_DETAILS_INVALID,
            ],
            'XR'    => [
                'XR',
                Status::FAILED,
                ErrorCode::BAD_REQUEST_PAYMENT_CARD_DETAILS_INVALID,
                PublicErrorCode::BAD_REQUEST_ERROR,
                PublicErrorDescription::BAD_REQUEST_PAYMENT_CARD_DETAILS_INVALID,
            ],
            '8'    => [
                '8',
                Status::FAILED,
                ErrorCode::GATEWAY_ERROR_BANK_OFFLINE,
                PublicErrorCode::GATEWAY_ERROR,
                PublicErrorDescription::GATEWAY_ERROR,
            ],
        ];
    }

    /**
     * @dataProvider errorCodeMapping
     */
    public function testErrorCodeMapping($code, $status, $internal, $public, $description)
    {
        $transformer = new TransactionTransformer([UpiTransaction\Entity::GATEWAY_ERROR_CODE => $code]);

        $output = [
            'internal_status'       => null,
            'internal_error_code'   => null,
        ];

        $transformer->checkForError($output);

        $this->assertSame($status, $output['internal_status'], 'Failed asserting internal status');
        $this->assertSame($internal, $output['internal_error_code'], 'Failed asserting internal error code');

        // We do not set error for pending transaction, these are mapped to
        // ErrorCode::GATEWAY_ERROR_TRANSACTION_PENDING
        if (in_array($code, ErrorMap::$pendingErrors))
        {
            $internal = ErrorCode::GATEWAY_ERROR_TRANSACTION_PENDING;
        }

        $error = new Error($internal);

        $this->assertSame($public, $error->getPublicErrorCode(), 'Failed asserting public error code');
        $this->assertSame($description, $error->getDescription(), 'Failed asserting public error description');
    }

    public function testErrorCodeTested()
    {
        $tested = $this->errorCodeMapping();

        $errorCodes = ErrorCodes::$errorCodeMap;

        $diff = array_diff_key($errorCodes, $tested);

        $this->assertCount(0, $diff, json_encode($diff));
    }
}
