<?php

namespace RZP\Gateway\Enach\Rbl;

use RZP\Models\Batch;
use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorDescription;
use RZP\Exception\GatewayErrorException;
use RZP\Models\Batch\Processor\Emandate\Debit\EnachRbl;

class ErrorCodes
{
    // Error codes in registration
    const M003 = 'M003';
    const M004 = 'M004';
    const M005 = 'M005';
    const M006 = 'M006';
    const M007 = 'M007';
    const M008 = 'M008';
    const M009 = 'M009';
    const M010 = 'M010';
    const M011 = 'M011';
    const M012 = 'M012';
    const M013 = 'M013';
    const M014 = 'M014';
    const M015 = 'M015';
    const M020 = 'M020';
    const M021 = 'M021';
    const M022 = 'M022';
    const M023 = 'M023';
    const M024 = 'M024';
    const M025 = 'M025';
    const M026 = 'M026';
    const M027 = 'M027';
    const M030 = 'M030';
    const M032 = 'M032';
    const M033 = 'M033';
    const M034 = 'M034';
    const M035 = 'M035';
    const M037 = 'M037';
    const M038 = 'M038';
    const M041 = 'M041';
    const M042 = 'M042';
    const M043 = 'M043';
    const M056 = 'M056';
    const M057 = 'M057';
    const M058 = 'M058';
    const M060 = 'M060';
    const M061 = 'M061';
    const M062 = 'M062';
    const M063 = 'M063';
    const M065 = 'M065';
    const M072 = 'M072';
    const M073 = 'M073';
    const M076 = 'M076';
    const M077 = 'M077';
    const M078 = 'M078';
    const M079 = 'M079';
    const M086 = 'M086';
    const M087 = 'M087';
    const M088 = 'M088';
    const M089 = 'M089';
    const M090 = 'M090';
    const M091 = 'M091';
    const M092 = 'M092';
    const M093 = 'M093';

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

    // Error codes modified by RBL upon receiving from NPCI
    const DE01_RBL = '01';
    const DE02_RBL = '02';
    const DE03_RBL = '03';
    const DE04_RBL = '04';
    const DE05_RBL = '05';
    const DE06_RBL = '06';
    const DE07_RBL = '07';
    const DE08_RBL = '08';
    const DE09_RBL = '09';

    protected static $registerErrorCodeDescMappings = [
        self::M003 => 'Drawers signature differs',
        self::M004 => 'Drawers signature required',
        self::M005 => 'Drawers signature to operate account not received',
        self::M006 => 'Drawers authority to operate account not received',
        self::M007 => 'Alterations require drawers authentication',
        self::M008 => 'Company for stamp required',
        self::M009 => 'Mandate in old format',
        self::M010 => 'Start date is mandatory',
        self::M011 => 'Payment stopped by attachment order',
        self::M012 => 'Payment stopped by court order',
        self::M013 => 'Withdrawal stopped owing to death of account holder',
        self::M014 => 'Withdrawal stopped owing to lunacy of account holder',
        self::M015 => 'Withdrawal stopped owing to insolvency of account holder',
        self::M020 => 'Rejected due to duplicate UMRN',
        self::M021 => 'Duplicate mandate first presented mandate already',
        self::M022 => 'Mandate presented in ACH as well as ECS',
        self::M023 => 'Refer to branch KYC not completed',
        self::M024 => 'Amount in words and figures differ',
        self::M025 => 'Present under proper mandate category',
        self::M026 => 'Account frozen or inoperative',
        self::M027 => 'Image not clear',
        self::M030 => 'Mandate registration not allowed for CC PF PPF act',
        self::M032 => 'Rejected as per customer confirmation',
        self::M033 => 'Invalid monthly EMI amount',
        self::M034 => 'Amount for EMI more than limit allowed for the act',
        self::M035 => 'Corporate name mismatch',
        self::M037 => 'Account closed',
        self::M038 => 'No such account',
        self::M041 => 'Account blocked',
        self::M042 => 'Account description does not tally',
        self::M043 => 'Nature of debit not allowed in account type',
        self::M056 => 'Mandate not registered - not maintaining req balance',
        self::M057 => 'Payer name mismatch',
        self::M058 => 'Name of beneficiary not provided or not legible',
        self::M060 => 'Invalid frequency',
        self::M061 => 'Frequency of payment not mentioned on mandate',
        self::M062 => 'Period of validity not mentioned or invalid end date',
        self::M063 => 'Invalid bank name',
        self::M065 => 'Fixed or maximum option not available on mandate',
        self::M072 => 'Data mismatch with mandate',
        self::M073 => 'Mandate incomplete',
        self::M076 => 'Data mismatch frequency and period',
        self::M077 => 'Data mismatch frequency and signature',
        self::M078 => 'Data mismatch period and signature',
        self::M079 => 'Data mismatch debit type and signature',
        self::M086 => 'Customer identifier mismatch',
        self::M087 => 'Incorrect amount',
        self::M088 => 'API - Data mismatch with customer info and data mandate',
        self::M089 => 'Aadhaar number mismatch in X509 certificate and mandate',
        self::M090 => 'Aadhaar number mismatch in X509 certificate and bank CBS',
        self::M091 => 'eSign signature is tampered or corrupt',
        self::M092 => 'Signed content does not tally with data mandate',
        self::M093 => 'Aadhaar not mapped to account number',
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

        // For RBL modified error codes
        self::DE01_RBL => 'Account closed or transferred',
        self::DE02_RBL => 'No such account',
        self::DE03_RBL => 'Account description does not tally',
        self::DE04_RBL => 'Balance insufficient',
        self::DE05_RBL => 'Not arranged for',
        self::DE06_RBL => 'Payment stopped by drawer',
        self::DE07_RBL => 'Payment stopped under court order/Account under litigation',
        self::DE08_RBL => 'Mandate not received/UMRN does not exist',
        self::DE09_RBL => 'Miscellaneous - Others',
    ];

    protected static $registerPublicErrorCodeMappings = [
        self::M003 => ErrorCode::BAD_REQUEST_PAYMENT_INVALID_WITHDRAWER_DATA,
        self::M004 => ErrorCode::BAD_REQUEST_PAYMENT_INVALID_WITHDRAWER_DATA,
        self::M005 => ErrorCode::BAD_REQUEST_PAYMENT_INVALID_WITHDRAWER_DATA,
        self::M006 => ErrorCode::BAD_REQUEST_PAYMENT_INVALID_WITHDRAWER_DATA,
        self::M007 => ErrorCode::BAD_REQUEST_PAYMENT_INVALID_WITHDRAWER_DATA,
        self::M008 => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
        self::M009 => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
        self::M010 => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
        self::M011 => ErrorCode::BAD_REQUEST_PAYMENT_ACCOUNT_WITHDRAWAL_FROZEN,
        self::M012 => ErrorCode::BAD_REQUEST_PAYMENT_ACCOUNT_WITHDRAWAL_FROZEN,
        self::M013 => ErrorCode::BAD_REQUEST_PAYMENT_ACCOUNT_WITHDRAWAL_FROZEN,
        self::M014 => ErrorCode::BAD_REQUEST_PAYMENT_ACCOUNT_WITHDRAWAL_FROZEN,
        self::M015 => ErrorCode::BAD_REQUEST_PAYMENT_ACCOUNT_WITHDRAWAL_FROZEN,
        self::M020 => ErrorCode::GATEWAY_ERROR_PAYMENT_DUPLICATE_REQUEST,
        self::M021 => ErrorCode::GATEWAY_ERROR_PAYMENT_DUPLICATE_REQUEST,
        self::M022 => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
        self::M023 => ErrorCode::BAD_REQUEST_PAYMENT_KYC_PENDING,
        self::M024 => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
        self::M025 => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
        self::M026 => ErrorCode::BAD_REQUEST_PAYMENT_ACCOUNT_WITHDRAWAL_FROZEN,
        self::M027 => ErrorCode::BAD_REQUEST_EMANDATE_REGISTRATION_FAILED,
        self::M030 => ErrorCode::BAD_REQUEST_PAYMENT_INVALID_ACCOUNT,
        self::M032 => ErrorCode::BAD_REQUEST_PAYMENT_CANCELLED_AT_EMANDATE_REGISTRATION,
        self::M033 => ErrorCode::GATEWAY_ERROR_PAYMENT_INVALID_AMOUNT,
        self::M034 => ErrorCode::GATEWAY_ERROR_PAYMENT_INVALID_AMOUNT,
        self::M035 => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
        self::M037 => ErrorCode::BAD_REQUEST_PAYMENT_INVALID_ACCOUNT,
        self::M038 => ErrorCode::BAD_REQUEST_PAYMENT_INVALID_ACCOUNT,
        self::M041 => ErrorCode::BAD_REQUEST_PAYMENT_INVALID_ACCOUNT,
        self::M042 => ErrorCode::BAD_REQUEST_PAYMENT_INVALID_ACCOUNT,
        self::M043 => ErrorCode::BAD_REQUEST_EMANDATE_REGISTRATION_FAILED,
        self::M056 => ErrorCode::BAD_REQUEST_EMANDATE_REGISTRATION_FAILED,
        self::M057 => ErrorCode::BAD_REQUEST_INVALID_ACCOUNT_HOLDER_NAME,
        self::M058 => ErrorCode::BAD_REQUEST_INVALID_ACCOUNT_HOLDER_NAME,
        self::M060 => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
        self::M061 => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
        self::M062 => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
        self::M063 => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS,
        self::M065 => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
        self::M072 => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
        self::M073 => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
        self::M076 => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
        self::M077 => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
        self::M078 => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
        self::M079 => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
        self::M086 => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
        self::M087 => ErrorCode::GATEWAY_ERROR_PAYMENT_INVALID_AMOUNT,
        self::M088 => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
        self::M089 => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
        self::M090 => ErrorCode::BAD_REQUEST_EMANDATE_AADHAAR_NOT_MAPPED,
        self::M091 => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
        self::M092 => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
        self::M093 => ErrorCode::BAD_REQUEST_EMANDATE_AADHAAR_NOT_MAPPED,
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

        self::DE01_RBL => ErrorCode::BAD_REQUEST_PAYMENT_INVALID_ACCOUNT,
        self::DE02_RBL => ErrorCode::BAD_REQUEST_PAYMENT_INVALID_ACCOUNT,
        self::DE03_RBL => ErrorCode::BAD_REQUEST_PAYMENT_INVALID_ACCOUNT,
        self::DE04_RBL => ErrorCode::BAD_REQUEST_PAYMENT_ACCOUNT_INSUFFICIENT_BALANCE,
        self::DE05_RBL => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
        self::DE06_RBL => ErrorCode::BAD_REQUEST_PAYMENT_CANCELLED_BY_CUSTOMER,
        self::DE07_RBL => ErrorCode::BAD_REQUEST_PAYMENT_ACCOUNT_WITHDRAWAL_FROZEN,
        self::DE08_RBL => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
        self::DE09_RBL => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
    ];

    public static function getRegistrationPublicErrorCode(array $row)
    {
        $errorCode = $row[Batch\Header::ENACH_REGISTER_RETURN_CODE] ?? '';

        self::throwInvalidResponseErrorIfCodeNotMapped($errorCode, self::$registerPublicErrorCodeMappings, $row);

        $errorCode = self::$registerPublicErrorCodeMappings[$errorCode];

        return self::getDescriptionFromErrorCode($errorCode);
    }

    public static function getDebitPublicErrorCode(array $row)
    {
        $errorCode = $row[EnachRbl::GATEWAY_ERROR_CODE];

        self::throwInvalidResponseErrorIfCodeNotMapped($errorCode, self::$debitPublicErrorCodeMappings, $row);

        return self::$debitPublicErrorCodeMappings[$errorCode];
    }

    protected static function throwInvalidResponseErrorIfCodeNotMapped($errorCode, array $mapping, array $content)
    {
        if (array_key_exists($errorCode, $mapping) === false)
        {
            // Log the whole row, that way it'd be easier to debug based on token id or
            // payment id in case it fails
            throw new GatewayErrorException(
                ErrorCode::GATEWAY_ERROR_INVALID_RESPONSE,
                '',
                'Gateway response code mapping not found.',
                $content);
        }
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
