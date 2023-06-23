<?php

namespace RZP\Gateway\Enach\Npci\Physical\Icici\Registration;

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorDescription;

class ErrorCodes
{
    const C003 = 'C003';
    const M041 = 'M041';
    const M037 = 'M037';
    const M042 = 'M042';
    const M007 = 'M007';
    const M024 = 'M024';
    const M034 = 'M034';
    const M008 = 'M008';
    const M035 = 'M035';
    const M079 = 'M079';
    const M076 = 'M076';
    const M077 = 'M077';
    const M078 = 'M078';
    const M072 = 'M072';
    const NCFE = 'NCFE';
    const M006 = 'M006';
    const M003 = 'M003';
    const M004 = 'M004';
    const M005 = 'M005';
    const M065 = 'M065';
    const M061 = 'M061';
    const M027 = 'M027';
    const M063 = 'M063';
    const M060 = 'M060';
    const M033 = 'M033';
    const M073 = 'M073';
    const M009 = 'M009';
    const M030 = 'M030';
    const M058 = 'M058';
    const M043 = 'M043';
    const M038 = 'M038';
    const M031 = 'M031';
    const C002 = 'C002';
    const C001 = 'C001';
    const M011 = 'M011';
    const M012 = 'M012';
    const M062 = 'M062';
    const M020 = 'M020';
    const M032 = 'M032';
    const M086 = 'M086';
    const M087 = 'M087';
    const M010 = 'M010';
    const NCEX = 'NCEX';
    const M013 = 'M013';
    const M015 = 'M015';
    const M014 = 'M014';
    const M026 = 'M026';
    const M021 = 'M021';
    const M022 = 'M022';
    const M036 = 'M036';
    const M056 = 'M056';
    const M025 = 'M025';
    const C005 = 'C005';
    const M057 = 'M057';
    const M090 = 'M090';
    const M093 = 'M093';
    const M055 = 'M055';
    const C004 = 'C004';
    const M068 = 'M068';
    const M074 = 'M074';
    const M075 = 'M075';
    const M080 = 'M080';
    const M084 = 'M084';
    const M082 = 'M082';
    const M085 = 'M085';
    const M083 = 'M083';
    const M081 = 'M081';
    const M050 = 'M050';
    const M049 = 'M049';
    const M066 = 'M066';
    const M052 = 'M052';
    const M051 = 'M051';
    const M053 = 'M053';
    const M054 = 'M054';
    const M019 = 'M019';
    const M067 = 'M067';
    const M091 = 'M091';
    const M092 = 'M092';
    const M002 = 'M002';
    const M071 = 'M071';
    //IQA Validation errors
    const I001 = 'Image Height should be less than or Equal To Allowed Height';
    const I002 = 'Image Width should be less than or Equal To Allowed Width';
    const I003 = 'Image X Resolution Should be less than or Equal To Allowed Value';
    const I004 = 'Image X Resolution Should be more than or Equal To Allowed Value';
    const I005 = 'Image Y Resolution Should be less than or Equal To Allowed Value';
    const I006 = 'Image Y Resolution Should be more than or Equal To Allowed Value';
    const I007 = 'Image is Too Bright';
    const I008 = 'Image is Too Dark';

    /*
     * In ack files of registration, error desc (not error code) is received for initial reject payments
     */
    const M096 = 'Instrument out dated, stale';
    const R001 = 'Range between mandate date and current business date exceeds limit days defined';
    const R002 = 'Mandate representation is not allowed! max allowed limit exceeded';
    const R003 = 'date of mandate should be before current business date';

    protected static $registerErrorCodeDescMappings = [
        self::C003 => 'Account mentioned on the mandate is closed to the destination bank\'s end',
        self::M041 => 'Account no mentioned on the mandate is blocked at the destination banks end',
        self::M037 => 'Account mentioned on the mandate is closed to the destination bank\'s end',
        self::M042 => 'Account details mentioned on the mandate do not tally with the account details at the destination bank',
        self::M007 => 'Alteration on the mandate requires counter signature',
        self::M024 => 'Amount mentioned on the mandate differs in words and figures',
        self::M034 => 'EMI amount mentioned on the mandate is greater that than the max amount limit set for the mandate',
        self::M008 => 'Company stamp required on the mandate for corporate accounts',
        self::M035 => 'Name mentioned o the mandate is different from the name maintained at the banks end.',
        self::M079 => 'Incorrect debit type/multiple option & signature mismatch',
        self::M076 => 'Incorrect frequency/multiple frequencies selected',
        self::M077 => 'Incorrect frequency/multiple frequencies selected & signature mismatch',
        self::M078 => 'Incorrect frequency/multiple frequencies selected & signature mismatch',
        self::M072 => 'Mismatch between the scanned image & supporting data. Mandate with rejection have to be reuploaded',
        self::M006 => 'Drawee does not have the authority to provide debit instructions at the bank',
        self::M003 => 'Signature on the mandate differs as against the signature maintained',
        self::M004 => 'No signature on the mandate',
        self::M005 => 'Drawee does not have the authority to provide debit instructions at the bank',
        self::M065 => 'Fixed or Maximum option not specified on mandate',
        self::M061 => 'Frequency of debit not selected',
        self::M027 => 'Image of the mandate not clear. This can be re-uploaded',
        self::M063 => 'Invalid Bank name mentioned on the mandate',
        self::M060 => 'Frequency of debit not selected/multiple frequencies selected',
        self::M033 => 'Amount mentioned on the mandate pertains to full loan amount instead of EMI',
        self::M073 => 'Mandate not filled completely',
        self::M009 => 'Mandate in Old Format',
        self::M030 => 'particular banks does not facilitate processing for CC (Cash Credit)/ PFF / PF and OD account.',
        self::M058 => 'Name of beneficiary not provided or not legible',
        self::M043 => 'ACH debits not allowed in the account type like CC',
        self::M038 => 'Account no mentioned on the mandate is not available with the bank',
        self::M031 => 'Mandate amended with change in account no',
        self::C002 => 'Mandate cancelled on corporate request',
        self::C001 => 'Mandate cancelled on customer request / This reject reason completely depends on customer confirmation.',
        self::M011 => 'Payment stopped by attachment order',
        self::M012 => 'Mandate cannot be accepted as all payments stopped by court from the said account',
        self::M062 => 'End date/until cancelled not mentioned on the mandate',
        self::M020 => 'Mandate rejected as duplicate UMRN received',
        self::M032 => 'Mandate rejected due to customer request',
        self::M086 => 'The customer identifier mentioned on the mandate does not match with the identifier available with the bank',
        self::M087 => 'The amount mentioned on the mandate is incorrect. i.e. full loan amount mentioned in place of EMI',
        self::M010 => 'Start date not mentioned on the mandate',
        self::NCEX => 'This reason is to be used as a default reason in case no action is taken on the mandate within the TAT of 5 days',
        self::M013 => 'NO further debits to the account can happen due to death of account holder.',
        self::M015 => 'No further debits to account can happen due to insolvency',
        self::M014 => 'NO further debits to the account can happen due to lunacy of account holder.',
        self::C005 => 'No one is operating account or account has been closed',
        self::M057 => 'Account holder name is mismatch in core banking system.For such cases client needs to share fresh mandate with correct account holder name which is maintained in bank system.',
        self::M090 => 'Aadhar no mismatch with core bank system.',
        self::M093 => 'Aadhar no not linked to account no',
        self::M055 => 'No one is operating account or account has been closed',
        self::M026 => 'Mandate cancelled as Account Closed or Frozen or Inopertive as per customer request.',
        self::C004 => 'Mandate cancelled as Account Closed or Frozen or Inopertive as per customer request.',
        self::M068 => 'Account type mentioned on mandate is different from core banking system',
        self::M074 => 'Account No mentioned on mandate and registered account no is different.',
        self::M075 => 'Account type mentioned on mandate and registered account type is different.',
        self::M080 => 'Amount mentioned on mandate and registered amount is different.',
        self::M084 => 'Bank name mentioned on mandate and registered bank name is different.',
        self::M082 => 'End Date mentioned on mandate and registered End Date is different.',
        self::M085 => 'Multiple fields selected.',
        self::M083 => 'Account holder name mentioned on mandate and registered Account holder name is different.',
        self::M081 => 'Start date mentioned on mandate and registered start date is different.',
        self::NCFE => 'This reason is to be used when the mandate is rejected due to bank not validating the same within the defined TAT.',
        self::M050 => 'Drawers signature is unreadable on mandate/ Signature not clear on mandate.',
        self::M049 => 'Drawers signature not updated in corporate banking system.',
        self::M021 => 'Mandate should be presented as per the frequency mentioned on it',
        self::M066 => 'Joint Account holders signature require',
        self::M056 => 'Require Average Quarterly balance',
        self::M052 => 'Account belongs to a minor and hence mandate cannot be registered',
        self::M051 => 'Account belongs to a NRE and hence mandate cannot be registered',
        self::M022 => 'Mandate with same details already registered under ECS and hence cannot be registered under ACH',
        self::M053 => 'Mandate registration not allowed for PF /particular banks does not facilitate processing for PF and account.',
        self::M054 => 'Mandate registration not allowed for PPF /particular banks does not facilitate processing for PPF and account.',
        self::M036 => 'Mandate amended with change in account no',
        self::M025 => 'Category code mismatch',
        self::M019 => 'Incomplete KYC documents submitted',
        self::M067 => 'In core bank system, thumb print is maintained. However, signature is affixed on mandate.',
        self::M091 => 'Signature maintained at bank system is corrupted.',
        self::M092 => 'Signature Mismatch',
        self::M002 => 'Illegible drawer signature. Please note that this reason would soon be discontinued',
        self::M071 => 'Company round stamp required',
        //IQA Validation errors
        self::I001 => 'Image Height should be less than or Equal To Allowed Height',
        self::I002 => 'Image Width should be less than or Equal To Allowed Width',
        self::I003 => 'Image X Resolution Should be less than or Equal To Allowed Value',
        self::I004 => 'Image X Resolution Should be more than or Equal To Allowed Value',
        self::I005 => 'Image Y Resolution Should be less than or Equal To Allowed Value',
        self::I006 => 'Image Y Resolution Should be more than or Equal To Allowed Value',
        self::I007 => 'Image is Too Bright',
        self::I008 => 'Image is Too Dark',
        self::M096 => 'date of mandate has exceeded 120 working days. Kindly create a new mandate',
        self::R001 => 'Range between mandate date and current business date exceeds 120 working days. Create a new mandate',
        self::R002 => 'Mandate representation is not allowed! max allowed limit exceeded. Kindly retry',
        self::R003 => 'date of mandate should be before current business date',
    ];

    protected static $registerInternalErrorCodeMappings = [
        self::C003 => ErrorCode::BAD_REQUEST_PAYMENT_ACCOUNT_WITHDRAWAL_FROZEN,
        self::C004 => ErrorCode::BAD_REQUEST_PAYMENT_ACCOUNT_WITHDRAWAL_FROZEN,
        self::M041 => ErrorCode::BAD_REQUEST_PAYMENT_ACCOUNT_WITHDRAWAL_FROZEN,
        self::M037 => ErrorCode::BAD_REQUEST_PAYMENT_ACCOUNT_WITHDRAWAL_FROZEN,
        self::M042 => ErrorCode::BAD_REQUEST_PAYMENT_INVALID_WITHDRAWER_DATA,
        self::M007 => ErrorCode::BAD_REQUEST_AUTHENTICATION_FAILED,
        self::M024 => ErrorCode::BAD_REQUEST_PAYMENT_WORDS_FIGURE_DIFFERS,
        self::M034 => ErrorCode::BAD_REQUEST_PAYMENT_EMI_LIMIT_EXCEED,
        self::M008 => ErrorCode::BAD_REQUEST_COMPANY_FOR_STAMP_MISSING,
        self::M035 => ErrorCode::BAD_REQUEST_CORPORATE_NAME_MISMATCH,
        self::M079 => ErrorCode::BAD_REQUEST_PAYMENT_INVALID_WITHDRAWER_DATA,
        self::M076 => ErrorCode::BAD_REQUEST_PAYMENT_INVALID_WITHDRAWER_DATA,
        self::M077 => ErrorCode::BAD_REQUEST_PAYMENT_INVALID_WITHDRAWER_DATA,
        self::M078 => ErrorCode::BAD_REQUEST_PAYMENT_INVALID_WITHDRAWER_DATA,
        self::M072 => ErrorCode::BAD_REQUEST_MANDATE_DATA_MISMATCH,
        self::M006 => ErrorCode::BAD_REQUEST_PAYMENT_INVALID_WITHDRAWER_DATA,
        self::M003 => ErrorCode::BAD_REQUEST_MISMATCH_SIGNATURE,
        self::M004 => ErrorCode::BAD_REQUEST_MISSING_SIGNATURE,
        self::M005 => ErrorCode::BAD_REQUEST_AUTHENTICATION_FAILED,
        self::M065 => ErrorCode::BAD_REQUEST_INVALID_TRANSACTION_AMOUNT,
        self::M061 => ErrorCode::BAD_REQUEST_INVALID_FREQ,
        self::M027 => ErrorCode::BAD_REQUEST_UNCLEAR_IMAGE,
        self::M063 => ErrorCode::BAD_REQUEST_INVALID_BANK_ACCOUNT,
        self::M060 => ErrorCode::BAD_REQUEST_INVALID_FREQ,
        self::M033 => ErrorCode::BAD_REQUEST_PAYMENT_EMI_LIMIT_EXCEED,
        self::M073 => ErrorCode::BAD_REQUEST_MANDATE_INCOMPLETE,
        self::M009 => ErrorCode::BAD_REQUEST_MANDATE_OLD_FORMAT,
        self::M030 => ErrorCode::BAD_REQUEST_PAYOUTS_NOT_ALLOWED_CURRENTLY,
        self::M058 => ErrorCode::BAD_REQUEST_INVALID_ACCOUNT_HOLDER_NAME,
        self::M043 => ErrorCode::BAD_REQUEST_PAYMENT_INVALID_DEBIT_TYPE,
        self::M038 => ErrorCode::BAD_REQUEST_INVALID_BANK_ACCOUNT,
        self::M031 => ErrorCode::BAD_REQUEST_INVALID_BANK_ACCOUNT,
        self::C002 => ErrorCode::BAD_REQUEST_EMANDATE_CANCELLED_INACTIVE,
        self::C001 => ErrorCode::BAD_REQUEST_EMANDATE_CANCELLED_INACTIVE,
        self::M011 => ErrorCode::BAD_REQUEST_PAYMENT_CANCELLED_BY_CUSTOMER,
        self::M012 => ErrorCode::BAD_REQUEST_PAYMENT_ACCOUNT_WITHDRAWAL_FROZEN,
        self::M062 => ErrorCode::BAD_REQUEST_PAYMENT_INVALID_PERIOD_SIGN,
        self::M020 => ErrorCode::BAD_REQUEST_DUPLICATE_UMRN,
        self::M032 => ErrorCode::BAD_REQUEST_PAYMENT_CANCELLED_BY_CUSTOMER,
        self::M086 => ErrorCode::BAD_REQUEST_INVALID_ACCOUNT_HOLDER_NAME,
        self::M087 => ErrorCode::BAD_REQUEST_INVALID_TRANSACTION_AMOUNT,
        self::NCEX => ErrorCode::BAD_REQUEST_PAYMENT_TIMED_OUT,
        self::NCFE => ErrorCode::BAD_REQUEST_PAYMENT_TIMED_OUT,
        self::M010 => ErrorCode::BAD_REQUEST_PAYMENT_INVALID_PERIOD_SIGN,
        self::M013 => ErrorCode::BAD_REQUEST_PAYMENT_ACCOUNT_WITHDRAWAL_FROZEN,
        self::M014 => ErrorCode::BAD_REQUEST_PAYMENT_ACCOUNT_WITHDRAWAL_FROZEN,
        self::M015 => ErrorCode::BAD_REQUEST_PAYMENT_ACCOUNT_WITHDRAWAL_FROZEN,
        self::C005 => ErrorCode::BAD_REQUEST_ACCOUNT_CLOSED,
        self::M057 => ErrorCode::BAD_REQUEST_MANDATE_USER_MISMATCH,
        self::M090 => ErrorCode::BAD_REQUEST_MANDATE_USER_MISMATCH,
        self::M093 => ErrorCode::BAD_REQUEST_EMANDATE_AADHAAR_NOT_MAPPED,
        self::M055 => ErrorCode::BAD_REQUEST_ACCOUNT_CLOSED,
        self::M026 => ErrorCode::BAD_REQUEST_PAYMENT_ACCOUNT_WITHDRAWAL_FROZEN,
        self::M068 => ErrorCode::BAD_REQUEST_MANDATE_DATA_MISMATCH,
        self::M074 => ErrorCode::BAD_REQUEST_MANDATE_DATA_MISMATCH,
        self::M075 => ErrorCode::BAD_REQUEST_MANDATE_DATA_MISMATCH,
        self::M080 => ErrorCode::BAD_REQUEST_MANDATE_DATA_MISMATCH,
        self::M081 => ErrorCode::BAD_REQUEST_MANDATE_DATA_MISMATCH,
        self::M082 => ErrorCode::BAD_REQUEST_MANDATE_DATA_MISMATCH,
        self::M084 => ErrorCode::BAD_REQUEST_MANDATE_DATA_MISMATCH,
        self::M085 => ErrorCode::BAD_REQUEST_EXTRA_FIELDS_PROVIDED,
        self::M083 => ErrorCode::BAD_REQUEST_MANDATE_USER_MISMATCH,
        self::M050 => ErrorCode::BAD_REQUEST_MISMATCH_SIGNATURE,
        self::M049 => ErrorCode::BAD_REQUEST_MISMATCH_SIGNATURE,
        self::M021 => ErrorCode::BAD_REQUEST_INVALID_FREQ,
        self::M066 => ErrorCode::BAD_REQUEST_MISSING_SIGNATURE,
        self::M056 => ErrorCode::BAD_REQUEST_PAYMENT_ACCOUNT_INSUFFICIENT_BALANCE,
        self::M052 => ErrorCode::BAD_REQUEST_RESTRICTED_USER_CANNOT_PERFORM_ACTION,
        self::M051 => ErrorCode::BAD_REQUEST_RESTRICTED_USER_CANNOT_PERFORM_ACTION,
        self::M022 => ErrorCode::BAD_REQUEST_CUSTOMER_ALREADY_EXISTS,
        self::M053 => ErrorCode::BAD_REQUEST_PAYMENT_ACCOUNT_WITHDRAWAL_FROZEN,
        self::M054 => ErrorCode::BAD_REQUEST_PAYMENT_ACCOUNT_WITHDRAWAL_FROZEN,
        self::M036 => ErrorCode::BAD_REQUEST_INVALID_BANK_ACCOUNT,
        self::M025 => ErrorCode::BAD_REQUEST_INVALID_SUBCATEGORY,
        self::M019 => ErrorCode::BAD_REQUEST_PAYMENT_KYC_PENDING,
        self::M067 => ErrorCode::BAD_REQUEST_MISMATCH_SIGNATURE,
        self::M091 => ErrorCode::BAD_REQUEST_SIGNATURE_ERROR,
        self::M092 => ErrorCode::BAD_REQUEST_MISMATCH_SIGNATURE,
        self::M002 => ErrorCode::BAD_REQUEST_MISMATCH_SIGNATURE,
        self::M071 => ErrorCode::BAD_REQUEST_COMPANY_FOR_STAMP_MISSING,
        //IQA Validation errors
        self::I001 => ErrorCode::BAD_REQUEST_INVALID_IMAGE_HEIGHT,
        self::I002 => ErrorCode::BAD_REQUEST_INVALID_IMAGE_WIDTH,
        self::I003 => ErrorCode::BAD_REQUEST_HIGH_IMAGE_X_RESOLUTION,
        self::I004 => ErrorCode::BAD_REQUEST_LOW_IMAGE_X_RESOLUTION,
        self::I005 => ErrorCode::BAD_REQUEST_HIGH_IMAGE_Y_RESOLUTION,
        self::I006 => ErrorCode::BAD_REQUEST_LOW_IMAGE_Y_RESOLUTION,
        self::I007 => ErrorCode::BAD_REQUEST_IMAGE_TOO_BRIGHT,
        self::I008 => ErrorCode::BAD_REQUEST_IMAGE_TOO_DARK,

        self::M096 => ErrorCode::BAD_REQUEST_MANDATE_CREATION_OUTDATED,
        self::R001 => ErrorCode::BAD_REQUEST_MANDATE_CREATION_OUTDATED,
        self::R002 => ErrorCode::BAD_REQUEST_MANDATE_REPRESENTATION_LIMIT_EXCEEDED,
        self::R003 => ErrorCode::BAD_REQUEST_FUTURE_MANDATE_CREATION_DATE,
    ];

    public static function getRegisterPublicErrorDescription($errCode)
    {
        $defaultErrorDesc = PublicErrorDescription::BAD_REQUEST_PAYMENT_FAILED;

        return self::$registerErrorCodeDescMappings[$errCode] ?? $defaultErrorDesc;
    }

    public static function getRegisterInternalErrorCode($errCode)
    {
        return self::$registerInternalErrorCodeMappings[$errCode] ?? ErrorCode::BAD_REQUEST_NACH_REGISTRATION_FAILED;
    }
}
