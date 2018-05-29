<?php

namespace RZP\Gateway\Enach\Rbl;

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorDescription;

class ErrorCodes
{
    // Error codes in registration
    const M03 = 'M03';
    const M04 = 'M04';
    const M05 = 'M05';
    const M06 = 'M06';
    const M07 = 'M07';
    const M08 = 'M08';
    const M09 = 'M09';
    const M10 = 'M10';
    const M11 = 'M11';
    const M12 = 'M12';
    const M13 = 'M13';
    const M14 = 'M14';
    const M15 = 'M15';
    const M20 = 'M20';
    const M21 = 'M21';
    const M22 = 'M22';
    const M23 = 'M23';
    const M24 = 'M24';
    const M25 = 'M25';
    const M26 = 'M26';
    const M27 = 'M27';
    const M30 = 'M30';
    const M32 = 'M32';
    const M33 = 'M33';
    const M34 = 'M34';
    const M35 = 'M35';
    const M37 = 'M37';
    const M38 = 'M38';
    const M41 = 'M41';
    const M42 = 'M42';
    const M43 = 'M43';
    const M56 = 'M56';
    const M57 = 'M57';
    const M58 = 'M58';
    const M60 = 'M60';
    const M61 = 'M61';
    const M62 = 'M62';
    const M63 = 'M63';
    const M65 = 'M65';
    const M72 = 'M72';
    const M73 = 'M73';
    const M76 = 'M76';
    const M77 = 'M77';
    const M78 = 'M78';
    const M79 = 'M79';
    const M86 = 'M86';
    const M87 = 'M87';
    const M88 = 'M88';
    const M89 = 'M89';
    const M90 = 'M90';
    const M91 = 'M91';
    const M92 = 'M92';
    const M93 = 'M93';

    // Debit error codes
    const DE01 = '1';
    const DE02 = '2';
    const DE03 = '3';
    const DE04 = '4';
    const DE05 = '5';
    const DE06 = '6';
    const DE07 = '7';
    const DE08 = '8';
    const DE09 = '9';
    const DE51 = '51';
    const DE52 = '52';
    const DE53 = '53';
    const DE54 = '54';
    const DE55 = '55';
    const DE56 = '56';
    const DE57 = '57';
    const DE58 = '58';
    const DE59 = '59';
    const DE60 = '60';
    const DE61 = '61';
    const DE68 = '68';
    const DE99 = '99';

    protected static $registerErrorCodeDescMappings = [
        self::M03 => 'Drawers signature differs',
        self::M04 => 'Drawers signature required',
        self::M05 => 'Drawers signature to operate account not received',
        self::M06 => 'Drawers authority to operate account not received',
        self::M07 => 'Alterations require drawers authentication',
        self::M08 => 'Company for stamp required',
        self::M09 => 'Mandate in old format',
        self::M10 => 'Start date is mandatory',
        self::M11 => 'Payment stopped by attachment order',
        self::M12 => 'Payment stopped by court order',
        self::M13 => 'Withdrawal stopped owing to death of account holder',
        self::M14 => 'Withdrawal stopped owing to lunacy of account holder',
        self::M15 => 'Withdrawal stopped owing to insolvency of account holder',
        self::M20 => 'Rejected due to duplicate UMRN',
        self::M21 => 'Duplicate mandate first presented mandate already',
        self::M22 => 'Mandate presented in ACH as well as ECS',
        self::M23 => 'Refer to branch KYC not completed',
        self::M24 => 'Amount in words and figures differ',
        self::M25 => 'Present under proper mandate category',
        self::M26 => 'Account frozen or inoperative',
        self::M27 => 'Image not clear',
        self::M30 => 'Mandate registration not allowed for CC PF PPF act',
        self::M32 => 'Rejected as per customer confirmation',
        self::M33 => 'Invalid monthly EMI amount',
        self::M34 => 'Amount for EMI more than limit allowed for the act',
        self::M35 => 'Corporate name mismatch',
        self::M37 => 'Account closed',
        self::M38 => 'No such account',
        self::M41 => 'Account blocked',
        self::M42 => 'Account description does not tally',
        self::M43 => 'Nature of debit not allowed in account type',
        self::M56 => 'Mandate not registered - not maintaining req balance',
        self::M57 => 'Payer name mismatch',
        self::M58 => 'Name of beneficiary not provided or not legible',
        self::M60 => 'Invalid frequency',
        self::M61 => 'Frequency of payment not mentioned on mandate',
        self::M62 => 'Period of validity not mentioned or invalid end date',
        self::M63 => 'Invalid bank name',
        self::M65 => 'Fixed or maximum option not available on mandate',
        self::M72 => 'Data mismatch with mandate',
        self::M73 => 'Mandate incomplete',
        self::M76 => 'Data mismatch frequency and period',
        self::M77 => 'Data mismatch frequency and signature',
        self::M78 => 'Data mismatch period and signature',
        self::M79 => 'Data mismatch debit type and signature',
        self::M86 => 'Customer identifier mismatch',
        self::M87 => 'Incorrect amount',
        self::M88 => 'API - Data mismatch with customer info and data mandate',
        self::M89 => 'Aadhaar number mismatch in X509 certificate and mandate',
        self::M90 => 'Aadhaar number mismatch in X509 certificate and bank CBS',
        self::M91 => 'eSign signature is tampered or corrupt',
        self::M92 => 'Signed content does not tally with data mandate',
        self::M93 => 'Aadhaar not mapped to account number',
    ];

    protected static $debitErrorCodeDescMappings = [
        self::DE01 => 'Account closed or transferred',
        self::DE02 => 'No such account',
        self::DE03 => 'Account description does not tally',
        self::DE04 => 'Balance insufficient',
        self::DE05 => 'Not arranged for',
        self::DE06 => 'Payment stopped by drawer',
        self::DE07 => 'Payment stopped under court order/Account under litigation',
        self::DE08 => 'Mandate not received/UMRN does not exist',
        self::DE09 => 'Miscellaneous - Others',
        self::DE51 => 'Miscellaneous - KYC documents pending',
        self::DE52 => 'Miscellaneous - Documents pending for account holder turning major',
        self::DE53 => 'Miscellaneous - A/c inactive (No transactions for the last 3 months)',
        self::DE54 => 'Miscellaneous - Dormant A/c (No transactions for the last 6 months)',
        self::DE55 => 'Miscellaneous - A/c in zero balance/No transactions have happened/'
                    . 'First transaction in cash or self cheque',
        self::DE56 => 'Miscellaneous - Simple account, First transaction to be from base branch',
        self::DE57 => 'Miscellaneous - Amount exceeds limit set on account by bank for debit per transaction',
        self::DE58 => 'Miscellaneous - Account reached maximum debit limit set on account by bank',
        self::DE59 => 'Miscellaneous - Network failure(CBS)',
        self::DE60 => 'Account holder expired',
        self::DE61 => 'Mandate cancelled',
        self::DE68 => 'A/c blocked or frozen',
        self::DE99 => 'Mark pending',
    ];

    protected static $registerPublicErrorCodeMappings = [
        self::M03 => ErrorCode::BAD_REQUEST_PAYMENT_INVALID_WITHDRAWER_DATA,
        self::M04 => ErrorCode::BAD_REQUEST_PAYMENT_INVALID_WITHDRAWER_DATA,
        self::M05 => ErrorCode::BAD_REQUEST_PAYMENT_INVALID_WITHDRAWER_DATA,
        self::M06 => ErrorCode::BAD_REQUEST_PAYMENT_INVALID_WITHDRAWER_DATA,
        self::M07 => ErrorCode::BAD_REQUEST_PAYMENT_INVALID_WITHDRAWER_DATA,
        self::M08 => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
        self::M09 => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
        self::M10 => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
        self::M11 => ErrorCode::BAD_REQUEST_PAYMENT_ACCOUNT_WITHDRAWAL_FROZEN,
        self::M12 => ErrorCode::BAD_REQUEST_PAYMENT_ACCOUNT_WITHDRAWAL_FROZEN,
        self::M13 => ErrorCode::BAD_REQUEST_PAYMENT_ACCOUNT_WITHDRAWAL_FROZEN,
        self::M14 => ErrorCode::BAD_REQUEST_PAYMENT_ACCOUNT_WITHDRAWAL_FROZEN,
        self::M15 => ErrorCode::BAD_REQUEST_PAYMENT_ACCOUNT_WITHDRAWAL_FROZEN,
        self::M20 => ErrorCode::GATEWAY_ERROR_PAYMENT_DUPLICATE_REQUEST,
        self::M21 => ErrorCode::GATEWAY_ERROR_PAYMENT_DUPLICATE_REQUEST,
        self::M22 => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
        self::M23 => ErrorCode::BAD_REQUEST_PAYMENT_KYC_PENDING,
        self::M24 => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
        self::M25 => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
        self::M26 => ErrorCode::BAD_REQUEST_PAYMENT_ACCOUNT_WITHDRAWAL_FROZEN,
        self::M27 => ErrorCode::BAD_REQUEST_EMANDATE_REGISTRATION_FAILED,
        self::M30 => ErrorCode::BAD_REQUEST_PAYMENT_INVALID_ACCOUNT,
        self::M32 => ErrorCode::BAD_REQUEST_PAYMENT_CANCELLED_AT_EMANDATE_REGISTRATION,
        self::M33 => ErrorCode::GATEWAY_ERROR_PAYMENT_INVALID_AMOUNT,
        self::M34 => ErrorCode::GATEWAY_ERROR_PAYMENT_INVALID_AMOUNT,
        self::M35 => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
        self::M37 => ErrorCode::BAD_REQUEST_PAYMENT_INVALID_ACCOUNT,
        self::M38 => ErrorCode::BAD_REQUEST_PAYMENT_INVALID_ACCOUNT,
        self::M41 => ErrorCode::BAD_REQUEST_PAYMENT_INVALID_ACCOUNT,
        self::M42 => ErrorCode::BAD_REQUEST_PAYMENT_INVALID_ACCOUNT,
        self::M43 => ErrorCode::BAD_REQUEST_EMANDATE_REGISTRATION_FAILED,
        self::M56 => ErrorCode::BAD_REQUEST_EMANDATE_REGISTRATION_FAILED,
        self::M57 => ErrorCode::BAD_REQUEST_INVALID_ACCOUNT_HOLDER_NAME,
        self::M58 => ErrorCode::BAD_REQUEST_INVALID_ACCOUNT_HOLDER_NAME,
        self::M60 => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
        self::M61 => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
        self::M62 => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
        self::M63 => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS,
        self::M65 => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
        self::M72 => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
        self::M73 => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
        self::M76 => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
        self::M77 => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
        self::M78 => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
        self::M79 => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
        self::M86 => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
        self::M87 => ErrorCode::GATEWAY_ERROR_PAYMENT_INVALID_AMOUNT,
        self::M88 => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
        self::M89 => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
        self::M90 => ErrorCode::BAD_REQUEST_EMANDATE_AADHAAR_NOT_MAPPED,
        self::M91 => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
        self::M92 => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
        self::M93 => ErrorCode::BAD_REQUEST_EMANDATE_AADHAAR_NOT_MAPPED,
    ];

    protected static $debitPublicErrorCodeMappings = [
        self::DE01 => ErrorCode::BAD_REQUEST_PAYMENT_INVALID_ACCOUNT,
        self::DE02 => ErrorCode::BAD_REQUEST_PAYMENT_INVALID_ACCOUNT,
        self::DE03 => ErrorCode::BAD_REQUEST_PAYMENT_INVALID_ACCOUNT,
        self::DE04 => ErrorCode::BAD_REQUEST_PAYMENT_ACCOUNT_INSUFFICIENT_BALANCE,
        self::DE05 => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
        self::DE06 => ErrorCode::BAD_REQUEST_PAYMENT_CANCELLED_BY_CUSTOMER,
        self::DE07 => ErrorCode::BAD_REQUEST_PAYMENT_ACCOUNT_WITHDRAWAL_FROZEN,
        self::DE08 => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
        self::DE09 => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
        self::DE51 => ErrorCode::BAD_REQUEST_PAYMENT_KYC_PENDING,
        self::DE52 => ErrorCode::BAD_REQUEST_PAYMENT_KYC_PENDING,
        self::DE53 => ErrorCode::BAD_REQUEST_PAYMENT_INVALID_ACCOUNT,
        self::DE54 => ErrorCode::BAD_REQUEST_PAYMENT_INVALID_ACCOUNT,
        self::DE55 => ErrorCode::BAD_REQUEST_PAYMENT_INVALID_ACCOUNT,
        self::DE56 => ErrorCode::BAD_REQUEST_PAYMENT_INVALID_ACCOUNT,
        self::DE57 => ErrorCode::BAD_REQUEST_PAYMENT_ACCOUNT_MAX_LIMIT_EXCEEDED,
        self::DE58 => ErrorCode::BAD_REQUEST_PAYMENT_ACCOUNT_MAX_LIMIT_EXCEEDED,
        self::DE59 => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
        self::DE60 => ErrorCode::BAD_REQUEST_PAYMENT_INVALID_ACCOUNT,
        self::DE61 => ErrorCode::BAD_REQUEST_PAYMENT_CANCELLED_BY_CUSTOMER,
        self::DE68 => ErrorCode::BAD_REQUEST_PAYMENT_INVALID_ACCOUNT,
        self::DE99 => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
    ];

    public static function getRegistrationPublicErrorCode(string $errorCode)
    {
        $defaultErrorCode = ErrorCode::GATEWAY_ERROR_TOKEN_REGISTRATION_FAILED;

        $errorCode = self::$registerPublicErrorCodeMappings[$errorCode] ?? $defaultErrorCode;

        return self::getDescriptionFromErrorCode($errorCode);
    }

    public static function getDebitPublicErrorCode(string $errorCode)
    {
        $defaultErrorCode = ErrorCode::BAD_REQUEST_PAYMENT_FAILED;

        return self::$debitPublicErrorCodeMappings[$errorCode] ?? $defaultErrorCode;
    }

    protected static function getDescriptionFromErrorCode($code)
    {
        $code = strtoupper($code);

        if (defined(PublicErrorDescription::class . '::' . $code))
        {
            return constant(PublicErrorDescription::class.'::'.$code);
        }
    }
}
