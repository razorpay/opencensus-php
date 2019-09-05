<?php

namespace RZP\Gateway\Netbanking\Sbi\Emandate;

class RegisterFileHeadings
{
    // Response file headings
    // Success
    const SR_NO                 = 'SerialNumber';
    const EMANDATE_TYPE         = 'eMandatesType';
    const UMRN                  = 'UMRN';
    const MERCHANT_ID           = 'MerchantID';
    const SCHEME_NAME           = 'SchemeName';
    const SUB_SCHEME            = 'SubScheme';
    const DEBIT_CUSTOMER_NAME   = 'DebitCustomerName';
    const DEBIT_ACCOUNT_NUMBER  = 'DebitAccountNo';
    const DEBIT_ACCOUNT_TYPE    = 'DebitAccountType';
    const DEBIT_IFSC            = 'DebitIFSC';
    const DEBIT_BANK_NAME       = 'DebitBankName';
    const AMOUNT_TYPE           = 'AmountType';
    const CUSTOMER_ID           = 'CustomerID';
    const PERIOD                = 'Period';
    const PAYMENT_TYPE          = 'PaymentType';
    const START_DATE            = 'StartDate';
    const END_DATE              = 'EndDate';
    const MOBILE                = 'Mobile';
    const EMAIL                 = 'Email';
    const OTHER_REF_NO          = 'Other Ref.Number';
    const PAN_NUMBER            = 'PANNumber';
    const AUTO_DEBIT_DATE       = 'AutoDebitDate';
    const AUTHENTICATION_MODE   = 'AuthenticationMode';
    const DATE_PROCESSED        = 'DateProcessed';
    const NO_OF_DAYS_PENDING    = 'NoOfDaysPending';
    const REJECT_REASON         = 'RejectReason';

    // Failure
    const TRANSACTION_DATE        = 'Transaction Date';
    const CUSTOMER_NAME           = 'Customer Name';
    const CUSTOMER_ACCOUNT_NUMBER = 'Customer Account Number';
    const MAX_AMOUNT              = 'Max Amount';
    const STATUS_DESCRIPTION      = 'Status Description';
    const START_DATE_REJECT_FILE  = 'Start_Date';
    const END_DATE_REJECT_FILE    = 'End_Date';
    const UMRN_REJECT_RILE        = 'Mandate ID/No';
    const SBI_REFERENCE_NO        = 'SBI reference number';
    const MODE_OF_VERIFICATION    = 'Mode of Verification';
    const AMOUNT_TYPE_REJECT_FILE = 'Amount Type';

    // common across both files
    const CUSTOMER_REF_NO       = 'CRN';
    const AMOUNT                = 'Amount';
    const STATUS                = 'Status';
    const FREQUENCY             = 'Frequency';
}
