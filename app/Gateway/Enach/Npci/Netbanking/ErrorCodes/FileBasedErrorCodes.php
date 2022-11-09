<?php

namespace RZP\Gateway\Enach\Npci\Netbanking\ErrorCodes;

use RZP\Error\Error;
use RZP\Models\Batch;
use RZP\Error\ErrorCode;
use RZP\Exception\GatewayErrorException;

class FileBasedErrorCodes
{
    const GATEWAY_ERROR_CODE    = 'gateway_error_code';
    const GATEWAY_ERROR_MESSAGE = 'gateway_error_message';

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
    const M031 = 'M031';
    const M032 = 'M032';
    const M033 = 'M033';
    const M034 = 'M034';
    const M035 = 'M035';
    const M037 = 'M037';
    const M038 = 'M038';
    const M041 = 'M041';
    const M042 = 'M042';
    const M043 = 'M043';
    const M049 = 'M049';
    const M050 = 'M050';
    const M051 = 'M051';
    const M052 = 'M052';
    const M053 = 'M053';
    const M054 = 'M054';
    const M055 = 'M055';
    const M056 = 'M056';
    const M057 = 'M057';
    const M058 = 'M058';
    const M060 = 'M060';
    const M061 = 'M061';
    const M062 = 'M062';
    const M063 = 'M063';
    const M065 = 'M065';
    const M066 = 'M066';
    const M067 = 'M067';
    const M068 = 'M068';
    const M072 = 'M072';
    const M073 = 'M073';
    const M074 = 'M074';
    const M075 = 'M075';
    const M076 = 'M076';
    const M077 = 'M077';
    const M078 = 'M078';
    const M079 = 'M079';
    const M080 = 'M080';
    const M081 = 'M081';
    const M082 = 'M082';
    const M083 = 'M083';
    const M084 = 'M084';
    const M085 = 'M085';
    const M086 = 'M086';
    const M087 = 'M087';
    const M088 = 'M088';
    const M089 = 'M089';
    const M090 = 'M090';
    const M091 = 'M091';
    const M092 = 'M092';
    const M093 = 'M093';

    // Debit error codes
    const DE01  = '01';
    const DE02  = '02';
    const DE03  = '03';
    const DE04  = '04';
    const DE4   = '4';
    const DE05  = '05';
    const DE06  = '06';
    const DE07  = '07';
    const DE8   = '8';
    const DE08  = '08';
    const DE09  = '09';
    const DE11  = '11';
    const DE12  = '12';
    const DE13  = '13';
    const DE14  = '14';
    const DE21  = '21';
    const DE22  = '22';
    const DE23  = '23';
    const DE24  = '24';
    const DE25  = '25';
    const DE26  = '26';
    const DE27  = '27';
    const DE28  = '28';
    const DE29  = '29';
    const DE30  = '30';
    const DE31  = '31';
    const DE32  = '32';
    const DE33  = '33';
    const DE34  = '34';
    const DE51  = '51';
    const DE52  = '52';
    const DE53  = '53';
    const DE54  = '54';
    const DE55  = '55';
    const DE56  = '56';
    const DE57  = '57';
    const DE58  = '58';
    const DE59  = '59';
    const DE60  = '60';
    const DE61  = '61';
    const DE68  = '68';
    const DE69  = '69';
    const DE70  = '70';
    const DE72  = '72';
    const DE73  = '73';
    const DE74  = '74';
    const DE75  = '75';
    const DE76  = '76';
    const DE77  = '77';
    const DE78  = '78';
    const DE79  = '79';
    const DE80  = '80';
    const DE81  = '81';
    const DE82  = '82';
    const DE83  = '83';
    const DE84  = '84';
    const DE85  = '85';
    const DE86  = '86';
    const DE87  = '87';
    const DE88  = '88';
    const DE89  = '89';
    const DE90  = '90';
    const DE91  = '91';
    const DE92  = '92';
    const DE93  = '93';
    const DE94  = '94';
    const DE95  = '95';
    const DE96  = '96';
    const DE97  = '97';
    const DE98  = '98';
    const DE99  = '99';
    const DE100 = ''; // for mapping empty error code.

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
        self::M031 => 'Not a CBS act no or old act no represent with CBS no',
        self::M032 => 'Rejected as per customer confirmation',
        self::M033 => 'Invalid monthly EMI amount',
        self::M034 => 'Amount for EMI more than limit allowed for the act',
        self::M035 => 'Corporate name mismatch',
        self::M037 => 'Account closed',
        self::M038 => 'No such account',
        self::M041 => 'Account blocked',
        self::M042 => 'Account description does not tally',
        self::M043 => 'Nature of debit not allowed in account type',
        self::M049 => 'Drawers signature not updated in bank CBS',
        self::M050 => 'Drawers signature illeligible in mandate form',
        self::M051 => 'Mandate not Registerd_NRE account',
        self::M052 => 'Mandate not Registerd_Minor account',
        self::M053 => 'Mandate registration not allowed for PF account',
        self::M054 => 'Mandate registration not allowed for PPF account',
        self::M055 => 'Account inoperative',
        self::M056 => 'Mandate not registered - not maintaining req balance',
        self::M057 => 'Payer name mismatch',
        self::M058 => 'Name of beneficiary not provided or not legible',
        self::M060 => 'Invalid frequency',
        self::M061 => 'Frequency of payment not mentioned on mandate',
        self::M062 => 'Period of validity not mentioned or invalid end date',
        self::M063 => 'Invalid bank name',
        self::M065 => 'Fixed or maximum option not available on mandate',
        self::M066 => 'Joint signature required',
        self::M067 => 'Thumb print in CBS but cust sign in mand vice versa',
        self::M068 => 'Account type in mandate is different from CBS',
        self::M072 => 'Data mismatch with mandate',
        self::M073 => 'Mandate incomplete',
        self::M074 => 'Data mismatch with image_account number',
        self::M075 => 'Data mismatch with image_account type',
        self::M076 => 'Data mismatch frequency and period',
        self::M077 => 'Data mismatch frequency and signature',
        self::M078 => 'Data mismatch period and signature',
        self::M079 => 'Data mismatch debit type and signature',
        self::M080 => 'Data mismatch with image_amount',
        self::M081 => 'Data mismatch with image_start date',
        self::M082 => 'Data mismatch with image_end date',
        self::M083 => 'Data mismatch with image_payer name',
        self::M084 => 'Data mismatch with image_debtor bank name',
        self::M085 => 'Data mismatch with image_more than one field',
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
        self::DE01  => 'Account closed or transferred',
        self::DE02  => 'No such account',
        self::DE03  => 'Account description does not tally',
        self::DE04  => 'Balance insufficient',
        self::DE4   => 'Balance insufficient',
        self::DE05  => 'Not arranged for',
        self::DE06  => 'Payment stopped by drawer',
        self::DE07  => 'Payment stopped under court order/Account under litigation',
        self::DE8   => 'Mandate not received/UMRN does not exist',
        self::DE08  => 'Mandate not received/UMRN does not exist',
        self::DE09  => 'Miscellaneous - Others',
        self::DE11  => 'Invalid IFSC/MICR code',
        self::DE12  => 'Mismatch in mandate frequency',
        self::DE13  => 'Duplicate transaction - transaction already debited',
        self::DE14  => 'Mandate expired',
        self::DE21  => 'Invalid UMRN or inactive mandate',
        self::DE22  => 'Mandate not valid for debit transaction',
        self::DE23  => 'Mismatch in mandate debtor account number',
        self::DE24  => 'Mismatch in mandate debtor bank',
        self::DE25  => 'Mismatch in mandate currency',
        self::DE26  => 'Amount exeeds mandate max amount',
        self::DE27  => 'Mandate amount mismatch',
        self::DE28  => 'Date is before mandate start date',
        self::DE29  => 'Date is after mandate end date',
        self::DE30  => 'Mandate user number mismatch',
        self::DE31  => 'Duplicate reference number',
        self::DE32  => 'Invalid date',
        self::DE33  => 'Item unwound',
        self::DE34  => 'Invalid amount',
        self::DE51  => 'Miscellaneous - KYC documents pending',
        self::DE52  => 'Miscellaneous - Documents pending for account holder turning major',
        self::DE53  => 'Miscellaneous - A/c inactive (No transactions for the last 3 months)',
        self::DE54  => 'Miscellaneous - Dormant A/c (No transactions for the last 6 months)',
        self::DE55  => 'Miscellaneous - A/c in zero balance/No transactions have happened/'
            . 'First transaction in cash or self cheque',
        self::DE56  => 'Miscellaneous - Simple account, First transaction to be from base branch',
        self::DE57  => 'Miscellaneous - Amount exceeds limit set on account by bank for debit per transaction',
        self::DE58  => 'Miscellaneous - Account reached maximum debit limit set on account by bank',
        self::DE59  => 'Miscellaneous - Network failure(CBS)',
        self::DE60  => 'Account holder expired',
        self::DE61  => 'Mandate cancelled',
        self::DE68  => 'A/c blocked or frozen',
        self::DE69  => "Customer Insolvent / Insane",
        self::DE70  => 'Customer to refer to the branch',
        self::DE72  => 'Item cancelled',
        self::DE73  => 'Settlement failed',
        self::DE74  => 'Invalid file format',
        self::DE75  => 'Transaction has been cancelled by user',
        self::DE76  => 'Invalid Aadhaar format',
        self::DE77  => 'Invalid currency',
        self::DE78  => 'Invalid Bank Identifier',
        self::DE79  => 'File sent after EOD and before SOD',
        self::DE80  => 'Wrong IIN',
        self::DE81  => 'Product is missing',
        self::DE82  => 'Item marked pending',
        self::DE83  => 'Unsupported field',
        self::DE84  => 'Invalid data format',
        self::DE85  => 'Participant not mapped to the product',
        self::DE86  => 'Invalid transaction code',
        self::DE87  => 'Missing original transaction',
        self::DE88  => 'Invalid original transaction',
        self::DE89  => 'Original date mismatch',
        self::DE90  => 'Amount does not match with original',
        self::DE91  => 'Information does not match with original',
        self::DE92  => 'Core error',
        self::DE93  => 'Wrong clearing house name in SFG',
        self::DE94  => 'Amount is Zero',
        self::DE95  => 'Inactive Aadhaar',
        self::DE96  => 'Aadhaar mapping does not exist/Aadhaar number not mapped to IIN',
        self::DE97  => 'Bad batch corporate user number/name',
        self::DE98  => 'Bad item corporate user number/name',
        self::DE99  => 'Mark pending',
        self::DE100 => 'Record Level Error:Invalid Mandate Info......'
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
        self::M022 => ErrorCode::GATEWAY_ERROR_PAYMENT_DUPLICATE_REQUEST,
        self::M023 => ErrorCode::BAD_REQUEST_PAYMENT_KYC_PENDING,
        self::M024 => ErrorCode::GATEWAY_ERROR_PAYMENT_INVALID_AMOUNT,
        self::M025 => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS,
        self::M026 => ErrorCode::BAD_REQUEST_PAYMENT_ACCOUNT_WITHDRAWAL_FROZEN,
        self::M027 => ErrorCode::BAD_REQUEST_EMANDATE_REGISTRATION_FAILED,
        self::M030 => ErrorCode::BAD_REQUEST_PAYMENT_INVALID_ACCOUNT,
        self::M031 => ErrorCode::BAD_REQUEST_PAYMENT_INVALID_ACCOUNT,
        self::M032 => ErrorCode::BAD_REQUEST_PAYMENT_CANCELLED_AT_EMANDATE_REGISTRATION,
        self::M033 => ErrorCode::GATEWAY_ERROR_PAYMENT_INVALID_AMOUNT,
        self::M034 => ErrorCode::BAD_REQUEST_PAYMENT_ACCOUNT_MAX_LIMIT_EXCEEDED,
        self::M035 => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS,
        self::M037 => ErrorCode::BAD_REQUEST_PAYMENT_INVALID_ACCOUNT,
        self::M038 => ErrorCode::BAD_REQUEST_PAYMENT_INVALID_ACCOUNT,
        self::M041 => ErrorCode::BAD_REQUEST_PAYMENT_ACCOUNT_WITHDRAWAL_FROZEN,
        self::M042 => ErrorCode::BAD_REQUEST_PAYMENT_INVALID_ACCOUNT,
        self::M043 => ErrorCode::BAD_REQUEST_EMANDATE_REGISTRATION_FAILED,
        self::M049 => ErrorCode::BAD_REQUEST_PAYMENT_INVALID_WITHDRAWER_DATA,
        self::M050 => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
        self::M051 => ErrorCode::BAD_REQUEST_PAYMENT_INVALID_ACCOUNT,
        self::M052 => ErrorCode::BAD_REQUEST_PAYMENT_INVALID_ACCOUNT,
        self::M053 => ErrorCode::BAD_REQUEST_PAYMENT_INVALID_ACCOUNT,
        self::M054 => ErrorCode::BAD_REQUEST_PAYMENT_INVALID_ACCOUNT,
        self::M055 => ErrorCode::BAD_REQUEST_PAYMENT_ACCOUNT_WITHDRAWAL_FROZEN,
        self::M056 => ErrorCode::BAD_REQUEST_EMANDATE_REGISTRATION_FAILED,
        self::M057 => ErrorCode::BAD_REQUEST_INVALID_ACCOUNT_HOLDER_NAME,
        self::M058 => ErrorCode::BAD_REQUEST_INVALID_ACCOUNT_HOLDER_NAME,
        self::M060 => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS,
        self::M061 => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS,
        self::M062 => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS,
        self::M063 => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS,
        self::M065 => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS,
        self::M066 => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS,
        self::M067 => ErrorCode::BAD_REQUEST_PAYMENT_INVALID_WITHDRAWER_DATA,
        self::M068 => ErrorCode::BAD_REQUEST_PAYMENT_INVALID_WITHDRAWER_DATA,
        self::M072 => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS,
        self::M073 => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS,
        self::M074 => ErrorCode::BAD_REQUEST_PAYMENT_INVALID_ACCOUNT,
        self::M075 => ErrorCode::BAD_REQUEST_PAYMENT_INVALID_ACCOUNT,
        self::M076 => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS,
        self::M077 => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS,
        self::M078 => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS,
        self::M079 => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS,
        self::M080 => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS,
        self::M081 => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS,
        self::M082 => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS,
        self::M083 => ErrorCode::BAD_REQUEST_PAYMENT_INVALID_WITHDRAWER_DATA,
        self::M084 => ErrorCode::BAD_REQUEST_PAYMENT_INVALID_WITHDRAWER_DATA,
        self::M085 => ErrorCode::BAD_REQUEST_PAYMENT_INVALID_WITHDRAWER_DATA,
        self::M086 => ErrorCode::BAD_REQUEST_PAYMENT_INVALID_WITHDRAWER_DATA,
        self::M087 => ErrorCode::GATEWAY_ERROR_PAYMENT_INVALID_AMOUNT,
        self::M088 => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS,
        self::M089 => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS,
        self::M090 => ErrorCode::BAD_REQUEST_EMANDATE_AADHAAR_NOT_MAPPED,
        self::M091 => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS,
        self::M092 => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS,
        self::M093 => ErrorCode::BAD_REQUEST_EMANDATE_AADHAAR_NOT_MAPPED,
    ];

    protected static $debitPublicErrorCodeMappings = [
        self::DE01  => ErrorCode::BAD_REQUEST_ACCOUNT_CLOSED,
        self::DE02  => ErrorCode::BAD_REQUEST_PAYMENT_INVALID_ACCOUNT,
        self::DE03  => ErrorCode::BAD_REQUEST_PAYMENT_INVALID_WITHDRAWER_DATA,
        self::DE04  => ErrorCode::BAD_REQUEST_PAYMENT_ACCOUNT_INSUFFICIENT_BALANCE,
        self::DE4   => ErrorCode::BAD_REQUEST_PAYMENT_ACCOUNT_INSUFFICIENT_BALANCE,
        self::DE05  => ErrorCode::BAD_REQUEST_PAYMENT_FAILED_EXCEEDS_ARRANGEMENT,
        self::DE06  => ErrorCode::BAD_REQUEST_PAYMENT_CANCELLED_BY_CUSTOMER,
        self::DE07  => ErrorCode::BAD_REQUEST_PAYMENT_ACCOUNT_WITHDRAWAL_FROZEN,
        self::DE8   => ErrorCode::BAD_REQUEST_EMANDATE_CANCELLED_INACTIVE,
        self::DE08  => ErrorCode::BAD_REQUEST_EMANDATE_CANCELLED_INACTIVE,
        self::DE09  => ErrorCode::GATEWAY_ERROR_DEBIT_FAILED,
        self::DE11  => ErrorCode::BAD_REQUEST_INVALID_IFSC_CODE,
        self::DE12  => ErrorCode::BAD_REQUEST_EMANDATE_FREQUENCY_MISMATCH,
        self::DE13  => ErrorCode::BAD_REQUEST_PAYMENT_ALREADY_PROCESSED,
        self::DE14  => ErrorCode::BAD_REQUEST_EMANDATE_EXPIRED,
        self::DE21  => ErrorCode::BAD_REQUEST_EMANDATE_CANCELLED_INACTIVE,
        self::DE22  => ErrorCode::BAD_REQUEST_EMANDATE_DEBIT_NOT_ALLOWED,
        self::DE23  => ErrorCode::BAD_REQUEST_PAYMENT_INVALID_ACCOUNT,
        self::DE24  => ErrorCode::BAD_REQUEST_PAYMENT_INVALID_ACCOUNT,
        self::DE25  => ErrorCode::BAD_REQUEST_PAYMENT_INVALID_AMOUNT_OR_CURRENCY,
        self::DE26  => ErrorCode::BAD_REQUEST_PAYMENT_ACCOUNT_MAX_LIMIT_EXCEEDED,
        self::DE27  => ErrorCode::BAD_REQUEST_PAYMENT_INVALID_AMOUNT_OR_CURRENCY,
        self::DE28  => ErrorCode::GATEWAY_ERROR_DEBIT_BEFORE_MANDATE_START,
        self::DE29  => ErrorCode::GATEWAY_ERROR_DEBIT_AFTER_MANDATE_END,
        self::DE30  => ErrorCode::BAD_REQUEST_MANDATE_USER_MISMATCH,
        self::DE31  => ErrorCode::GATEWAY_ERROR_PAYMENT_DUPLICATE_REQUEST,
        self::DE32  => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS,
        self::DE33  => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS,
        self::DE34  => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS,
        self::DE51  => ErrorCode::BAD_REQUEST_PAYMENT_KYC_PENDING,
        self::DE52  => ErrorCode::BAD_REQUEST_PAYMENT_KYC_PENDING,
        self::DE53  => ErrorCode::BAD_REQUEST_PAYMENT_ACCOUNT_WITHDRAWAL_FROZEN,
        self::DE54  => ErrorCode::BAD_REQUEST_PAYMENT_ACCOUNT_WITHDRAWAL_FROZEN,
        self::DE55  => ErrorCode::BAD_REQUEST_PAYMENT_ACCOUNT_WITHDRAWAL_FROZEN,
        self::DE56  => ErrorCode::BAD_REQUEST_PAYMENT_ACCOUNT_WITHDRAWAL_FROZEN,
        self::DE57  => ErrorCode::BAD_REQUEST_PAYMENT_ACCOUNT_MAX_LIMIT_EXCEEDED,
        self::DE58  => ErrorCode::BAD_REQUEST_PAYMENT_ACCOUNT_MAX_LIMIT_EXCEEDED,
        self::DE59  => ErrorCode::GATEWAY_ERROR_ISSUER_DOWN,
        self::DE60  => ErrorCode::BAD_REQUEST_ACCOUNT_HOLDER_EXPIRED,
        self::DE61  => ErrorCode::BAD_REQUEST_EMANDATE_CANCELLED_INACTIVE,
        self::DE68  => ErrorCode::BAD_REQUEST_PAYMENT_ACCOUNT_WITHDRAWAL_FROZEN,
        self::DE69  => ErrorCode::BAD_REQUEST_PAYMENT_ACCOUNT_WITHDRAWAL_FROZEN,
        self::DE70  => ErrorCode::BAD_REQUEST_EMANDATE_REGISTRATION_ACTION_NEEDED,
        self::DE72  => ErrorCode::BAD_REQUEST_PAYMENT_CANCELLED_BY_USER,
        self::DE73  => ErrorCode::BAD_REQUEST_EMANDATE_SETTLEMENT_FAILED,
        self::DE74  => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS,
        self::DE75  => ErrorCode::BAD_REQUEST_PAYMENT_CANCELLED_BY_USER,
        self::DE76  => ErrorCode::BAD_REQUEST_PAYMENT_INVALID_WITHDRAWER_DATA,
        self::DE77  => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS,
        self::DE78  => ErrorCode::BAD_REQUEST_PAYMENT_INVALID_WITHDRAWER_DATA,
        self::DE79  => ErrorCode::BAD_REQUEST_EMANDATE_DEBIT_TIME_BREACHED,
        self::DE80  => ErrorCode::BAD_REQUEST_PAYMENT_FAILED_DUE_TO_INVALID_BIN,
        self::DE81  => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS,
        self::DE82  => ErrorCode::GATEWAY_ERROR_TRANSACTION_PENDING,
        self::DE83  => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS,
        self::DE84  => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS,
        self::DE85  => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS,
        self::DE86  => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS,
        self::DE87  => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS,
        self::DE88  => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS,
        self::DE89  => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS,
        self::DE90  => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS,
        self::DE91  => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS,
        self::DE92  => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS,
        self::DE93  => ErrorCode::BAD_REQUEST_PAYMENT_INVALID_WITHDRAWER_DATA,
        self::DE94  => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS,
        self::DE95  => ErrorCode::BAD_REQUEST_EMANDATE_AADHAAR_NOT_MAPPED,
        self::DE96  => ErrorCode::BAD_REQUEST_EMANDATE_AADHAAR_NOT_MAPPED,
        self::DE97  => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS,
        self::DE98  => ErrorCode::GATEWAY_ERROR_INVALID_PARAMETERS,
        self::DE99  => ErrorCode::GATEWAY_ERROR_REQUEST_ERROR,
        self::DE100 => ErrorCode::BAD_REQUEST_EMANDATE_INACTIVE,
    ];

    public static function getRegistrationPublicErrorCode(array $row)
    {
        $errorCode = $row[Batch\Header::ENACH_NPCI_NETBANKING_REGISTER_STATUS_CODE] ?? '';

        $defaultErrorCode = ErrorCode::GATEWAY_ERROR_TOKEN_REGISTRATION_FAILED;

        $errorCode = self::$registerPublicErrorCodeMappings[$errorCode] ?? $defaultErrorCode;

        return self::getDescriptionFromErrorCode($errorCode);
    }

    public static function getDebitPublicErrorCode(array $row)
    {
        $errorCode = $row[self::GATEWAY_ERROR_CODE] ?? '';

        $gatewayErrorDesc = $row[self::GATEWAY_ERROR_MESSAGE] ?? '';

        if (empty($errorCode))
        {
            $errorCode = self::getErrorCodeForErrorDesc($gatewayErrorDesc);
        }

        $defaultErrorCode = ErrorCode::BAD_REQUEST_PAYMENT_FAILED;

        return self::$debitPublicErrorCodeMappings[$errorCode] ?? $defaultErrorCode;
    }

    public static function getErrorCodeForErrorDesc($errorDesc)
    {
        foreach (self::$debitErrorCodeDescMappings as $gatewayErrorCode => $gatewayErrorDesc)
        {
            if ($errorDesc === $gatewayErrorDesc)
            {
                return $gatewayErrorCode;
            }
        }
        return null;
    }

    protected static function throwInvalidResponseErrorIfCodeNotMapped($errorCode, array $mapping, array $content)
    {
        if (isset($mapping[$errorCode]) === false)
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
        $error = new Error($code);

        return $error->getDescription();
    }
}
