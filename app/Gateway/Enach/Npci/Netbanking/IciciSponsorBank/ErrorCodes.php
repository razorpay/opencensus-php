<?php

namespace RZP\Gateway\Enach\Npci\Netbanking\IciciSponsorBank;

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorDescription;

class ErrorCodes
{
    const GATEWAY_ERROR_CODE    = 'gateway_error_code';
    const GATEWAY_ERROR_MESSAGE = 'gateway_error_message';

    // Debit error codes
    const DE01 = '01';
    const DE02 = '02';
    const DE03 = '03';
    const DE04 = '04';
    const DE05 = '05';
    const DE06 = '06';
    const DE07 = '07';
    const DE08 = '08';
    const DE09 = '09';
    const DE11 = '11';
    const DE12 = '12';
    const DE13 = '13';
    const DE14 = '14';
    const DE15 = '15';
    const DE16 = '16';
    const DE17 = '17';
    const DE21 = '21';
    const DE22 = '22';
    const DE23 = '23';
    const DE24 = '24';
    const DE25 = '25';
    const DE26 = '26';
    const DE27 = '27';
    const DE28 = '28';
    const DE29 = '29';
    const DE30 = '30';
    const DE31 = '31';
    const DE32 = '32';
    const DE33 = '33';
    const DE34 = '34';
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
    const DE69 = '69';
    const DE70 = '70';
    const DE72 = '72';
    const DE73 = '73';
    const DE74 = '74';
    const DE75 = '75';
    const DE76 = '76';
    const DE77 = '77';
    const DE78 = '78';
    const DE79 = '79';
    const DE80 = '80';
    const DE81 = '81';
    const DE82 = '82';
    const DE83 = '83';
    const DE84 = '84';
    const DE85 = '85';
    const DE86 = '86';
    const DE87 = '87';
    const DE88 = '88';
    const DE89 = '89';
    const DE90 = '90';
    const DE91 = '91';
    const DE92 = '92';
    const DE93 = '93';
    const DE94 = '94';
    const DE95 = '95';
    const DE96 = '96';
    const DE97 = '97';
    const DE98 = '98';
    const DE99 = '99';

    protected static $debitErrorCodeDescMappings = [
        self::DE01 => 'Account Closed',
        self::DE02 => 'No such account',
        self::DE03 => 'Account description does not tally',
        self::DE04 => 'Balance insufficient',
        self::DE05 => 'Not arranged for',
        self::DE06 => 'Payment stopped by drawer',
        self::DE07 => 'Payment stopped under court order/Account under litigation',
        self::DE08 => 'Mandate Not Received',
        self::DE09 => 'Miscellaneous - Others',
        self::DE11 => 'Invalid IFSC/MICR code',
        self::DE12 => 'Mismatch in mandate frequency',
        self::DE13 => 'Duplicate transaction - transaction already debited either under ACH or NACH debit (ECS)',
        self::DE14 => 'Mandate expired',
        self::DE15 => 'Incorrect amount-Mismatch between mandate & transaction',
        self::DE16 => 'Customer name mismatch',
        self::DE17 => 'Returned as per customer request',
        self::DE21 => 'Invalid UMRN or inactive mandate',
        self::DE22 => 'Mandate not valid for debit transaction',
        self::DE23 => 'Mismatch in mandate debtor account number',
        self::DE24 => 'Mismatch in mandate debtor bank',
        self::DE25 => 'Mismatch in mandate currency',
        self::DE26 => 'Amount exceeds mandate max amount',
        self::DE27 => 'Mandate amount mismatch',
        self::DE28 => 'Date is before mandate start date',
        self::DE29 => 'Date is after mandate end date',
        self::DE30 => 'Mandate user number mismatch',
        self::DE31 => 'Duplicate reference number',
        self::DE32 => 'Invalid date',
        self::DE33 => 'Item unwound',
        self::DE34 => 'Invalid amount',
        self::DE51 => 'KYC Documents Pending',
        self::DE52 => 'Documents Pending for Account Holder turning Major',
        self::DE53 => 'Account Inoperative',
        self::DE54 => 'Dormant Account',
        self::DE55 => 'A/c in Zero Balance/No Transactions have Happened, First Transaction in Cash or Self Cheque',
        self::DE56 => 'Small account , First Transaction to be from Base Branch',
        self::DE57 => 'Amount Exceeds limit set on Account by Bank for Debit per Transaction',
        self::DE58 => 'Account reached maximum Debit limit set on account by Bank',
        self::DE59 => 'Network Failure (CBS)',
        self::DE60 => 'Account holder expired',
        self::DE61 => 'Mandate cancelled',
        self::DE68 => 'A/c Blocked or Frozen',
        self::DE69 => "Customer Insolvent / Insane",
        self::DE70 => 'Customer to refer to the branch',
        self::DE72 => 'Item cancelled',
        self::DE73 => 'Settlement failed',
        self::DE74 => 'Invalid file format',
        self::DE75 => 'Transaction has been cancelled by user',
        self::DE76 => 'Invalid Aadhaar format',
        self::DE77 => 'Invalid currency',
        self::DE78 => 'Invalid Bank Identifier',
        self::DE79 => 'Item sent before SOD or after FC',
        self::DE80 => 'Wrong IIN',
        self::DE81 => 'Product is missing',
        self::DE82 => 'Item marked pending',
        self::DE83 => 'Unsupported field',
        self::DE84 => 'Invalid data format',
        self::DE85 => 'Participant not mapped to the product',
        self::DE86 => 'Invalid transaction code',
        self::DE87 => 'Missing original transaction',
        self::DE88 => 'Invalid original transaction',
        self::DE89 => 'Original date mismatch',
        self::DE90 => 'Amount does not match with original',
        self::DE91 => 'Information does not match with original',
        self::DE92 => 'Core error',
        self::DE93 => 'Wrong clearing house name in SFG',
        self::DE94 => 'Amount is Zero',
        self::DE95 => 'Aadhar name de-seeded from NPCI mapper by bank - customer to contact his \ her bank',
        self::DE96 => 'Aadhaar mapping does not exist/Aadhaar number not mapped to IIN',
        self::DE97 => 'Bad batch corporate user number/name',
        self::DE98 => 'Bad item corporate user number/name',
        self::DE99 => 'Too many mark pending returns',
    ];

    protected static $debitInternalErrorCodeMappings = [
        self::DE01 => ErrorCode::BAD_REQUEST_ACCOUNT_CLOSED,
        self::DE02 => ErrorCode::BAD_REQUEST_PAYMENT_INVALID_ACCOUNT,
        self::DE03 => ErrorCode::BAD_REQUEST_PAYMENT_INVALID_WITHDRAWER_DATA,
        self::DE04 => ErrorCode::BAD_REQUEST_PAYMENT_ACCOUNT_INSUFFICIENT_BALANCE,
        self::DE05 => ErrorCode::GATEWAY_ERROR_DEBIT_FAILED,
        self::DE06 => ErrorCode::BAD_REQUEST_PAYMENT_CANCELLED_BY_CUSTOMER,
        self::DE07 => ErrorCode::BAD_REQUEST_PAYMENT_ACCOUNT_WITHDRAWAL_FROZEN,
        self::DE08 => ErrorCode::BAD_REQUEST_EMANDATE_CANCELLED_INACTIVE,
        self::DE09 => ErrorCode::GATEWAY_ERROR_DEBIT_FAILED,
        self::DE11 => ErrorCode::BAD_REQUEST_INVALID_IFSC_CODE,
        self::DE12 => ErrorCode::BAD_REQUEST_EMANDATE_FREQUENCY_MISMATCH,
        self::DE13 => ErrorCode::BAD_REQUEST_PAYMENT_ALREADY_PROCESSED,
        self::DE14 => ErrorCode::BAD_REQUEST_EMANDATE_EXPIRED,
        self::DE15 => ErrorCode::BAD_REQUEST_INVALID_TRANSACTION_AMOUNT,
        self::DE16 => ErrorCode::BAD_REQUEST_PAYMENT_INVALID_WITHDRAWER_DATA,
        self::DE17 => ErrorCode::BAD_REQUEST_PAYMENT_CANCELLED_BY_CUSTOMER,
        self::DE21 => ErrorCode::BAD_REQUEST_EMANDATE_CANCELLED_INACTIVE,
        self::DE22 => ErrorCode::BAD_REQUEST_EMANDATE_DEBIT_NOT_ALLOWED,
        self::DE23 => ErrorCode::BAD_REQUEST_PAYMENT_INVALID_ACCOUNT,
        self::DE24 => ErrorCode::BAD_REQUEST_PAYMENT_INVALID_ACCOUNT,
        self::DE25 => ErrorCode::BAD_REQUEST_PAYMENT_INVALID_AMOUNT_OR_CURRENCY,
        self::DE26 => ErrorCode::BAD_REQUEST_PAYMENT_ACCOUNT_MAX_LIMIT_EXCEEDED,
        self::DE27 => ErrorCode::BAD_REQUEST_PAYMENT_INVALID_AMOUNT_OR_CURRENCY,
        self::DE28 => ErrorCode::GATEWAY_ERROR_DEBIT_BEFORE_MANDATE_START,
        self::DE29 => ErrorCode::GATEWAY_ERROR_DEBIT_AFTER_MANDATE_END,
        self::DE30 => ErrorCode::BAD_REQUEST_MANDATE_USER_MISMATCH,
        self::DE31 => ErrorCode::GATEWAY_ERROR_PAYMENT_DUPLICATE_REQUEST,
        self::DE32 => ErrorCode::GATEWAY_ERROR_INVALID_DATE_FORMAT,
        self::DE33 => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS,
        self::DE34 => ErrorCode::GATEWAY_ERROR_PAYMENT_INVALID_AMOUNT,
        self::DE51 => ErrorCode::BAD_REQUEST_PAYMENT_KYC_PENDING,
        self::DE52 => ErrorCode::BAD_REQUEST_PAYMENT_KYC_PENDING,
        self::DE53 => ErrorCode::BAD_REQUEST_PAYMENT_ACCOUNT_WITHDRAWAL_FROZEN,
        self::DE54 => ErrorCode::BAD_REQUEST_PAYMENT_ACCOUNT_WITHDRAWAL_FROZEN,
        self::DE55 => ErrorCode::BAD_REQUEST_PAYMENT_ACCOUNT_WITHDRAWAL_FROZEN,
        self::DE56 => ErrorCode::BAD_REQUEST_PAYMENT_ACCOUNT_WITHDRAWAL_FROZEN,
        self::DE57 => ErrorCode::BAD_REQUEST_PAYMENT_ACCOUNT_MAX_LIMIT_EXCEEDED,
        self::DE58 => ErrorCode::BAD_REQUEST_PAYMENT_ACCOUNT_MAX_LIMIT_EXCEEDED,
        self::DE59 => ErrorCode::GATEWAY_ERROR_ISSUER_DOWN,
        self::DE60 => ErrorCode::BAD_REQUEST_PAYMENT_ACCOUNT_WITHDRAWAL_FROZEN,
        self::DE61 => ErrorCode::BAD_REQUEST_EMANDATE_CANCELLED_INACTIVE,
        self::DE68 => ErrorCode::BAD_REQUEST_PAYMENT_ACCOUNT_WITHDRAWAL_FROZEN,
        self::DE69 => ErrorCode::BAD_REQUEST_PAYMENT_ACCOUNT_WITHDRAWAL_FROZEN,
        self::DE70 => ErrorCode::BAD_REQUEST_EMANDATE_REGISTRATION_ACTION_NEEDED,
        self::DE72 => ErrorCode::BAD_REQUEST_PAYMENT_CANCELLED_BY_USER,
        self::DE73 => ErrorCode::BAD_REQUEST_EMANDATE_SETTLEMENT_FAILED,
        self::DE74 => ErrorCode::GATEWAY_ERROR_FILE_ERROR,
        self::DE75 => ErrorCode::BAD_REQUEST_PAYMENT_CANCELLED_BY_USER,
        self::DE76 => ErrorCode::BAD_REQUEST_PAYMENT_INVALID_WITHDRAWER_DATA,
        self::DE77 => ErrorCode::GATEWAY_ERROR_PAYMENT_INVALID_CURRENCY,
        self::DE78 => ErrorCode::BAD_REQUEST_PAYMENT_INVALID_WITHDRAWER_DATA,
        self::DE79 => ErrorCode::BAD_REQUEST_EMANDATE_DEBIT_TIME_BREACHED,
        self::DE80 => ErrorCode::BAD_REQUEST_PAYMENT_FAILED_DUE_TO_INVALID_BIN,
        self::DE81 => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS,
        self::DE82 => ErrorCode::GATEWAY_ERROR_TRANSACTION_PENDING,
        self::DE83 => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS,
        self::DE84 => ErrorCode::GATEWAY_ERROR_INVALID_PAYMENT_DATA,
        self::DE85 => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS,
        self::DE86 => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS,
        self::DE87 => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS,
        self::DE88 => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS,
        self::DE89 => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS,
        self::DE90 => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS,
        self::DE91 => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS,
        self::DE92 => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS,
        self::DE93 => ErrorCode::BAD_REQUEST_PAYMENT_INVALID_WITHDRAWER_DATA,
        self::DE94 => ErrorCode::GATEWAY_ERROR_PAYMENT_INVALID_AMOUNT,
        self::DE95 => ErrorCode::BAD_REQUEST_EMANDATE_AADHAAR_NOT_MAPPED,
        self::DE96 => ErrorCode::BAD_REQUEST_EMANDATE_AADHAAR_NOT_MAPPED,
        self::DE97 => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS,
        self::DE98 => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS,
        self::DE99 => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
    ];

    public static function getDebitInternalErrorCode(array $row)
    {
        $errorCode = $row[self::GATEWAY_ERROR_CODE] ?? '';

        $defaultErrorCode = ErrorCode::BAD_REQUEST_PAYMENT_FAILED;

        return self::$debitInternalErrorCodeMappings[$errorCode] ?? $defaultErrorCode;
    }

    public static function getDebitPublicErrorDescription($errCode)
    {
        $defaultErrorDesc = PublicErrorDescription::BAD_REQUEST_PAYMENT_FAILED;

        return self::$debitErrorCodeDescMappings[$errCode] ?? $defaultErrorDesc;
    }
}
