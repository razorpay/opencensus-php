<?php

namespace RZP\Models\FundTransfer\Hdfc;

class Headings
{
    const TRANSACTION_TYPE              = 'Transaction Type';
    const BENEFICIARY_CODE              = 'Beneficiary Code';
    const BENEFICIARY_ACCOUNT_NUMBER    = 'Beneficiary Account Number';
    const INSTRUMENT_AMOUNT             = 'Instrument Amount';
    const BENEFICIARY_NAME              = 'Beneficiary Name';
    const DRAWEE_LOCATION               = 'Drawee Location';
    const PRINT_LOCATION                = 'Print Location';
    const BENE_ADDRESS_1                = 'Bene Address 1';
    const BENE_ADDRESS_2                = 'Bene Address 2';
    const BENE_ADDRESS_3                = 'Bene Address 3';
    const BENE_ADDRESS_4                = 'Bene Address 4';
    const BENE_ADDRESS_5                = 'Bene Address 5';
    const INSTRUCTION_REFERENCE_NUMBER  = 'Instruction Reference Number';
    const CUSTOMER_REFERENCE_NUMBER     = 'Customer Reference Number';
    const PAYMENT_DETAILS_1             = 'Payment details 1';
    const PAYMENT_DETAILS_2             = 'Payment details 2';
    const PAYMENT_DETAILS_3             = 'Payment details 3';
    const PAYMENT_DETAILS_4             = 'Payment details 4';
    const PAYMENT_DETAILS_5             = 'Payment details 5';
    const PAYMENT_DETAILS_6             = 'Payment details 6';
    const PAYMENT_DETAILS_7             = 'Payment details 7';
    const CHEQUE_NUMBER                 = 'Cheque Number';
    const TRANSACTION_DATE              = 'Chq / Trn Date';
    const MICR_NUMBER                   = 'MICR Number';
    const IFC_CODE                      = 'IFC Code';
    const BENE_BANK_NAME                = 'Bene Bank Name';
    const BENE_BANK_BRANCH_NAME         = 'Bene Bank Branch Name';
    const BENEFICIARY_EMAIL_ID          = 'Beneficiary email id';

    //Fields available in reverse file
    const BANK_REFERENCE_NO             = 'Bank Ref No';
    const TRANSACTION_STATUS            = 'Transaction Status';
    const REJECT_REASON                 = 'Reject Reason';
    const UTR                           = 'UTR No for RTGS';

    /**
     * Gives the attributes of records in order of expected format
     * In order of its occurrence
     *
     * @return array
     */
    public static function getRequestFileHeadings()
    {
        return [
            self::TRANSACTION_TYPE,
            self::BENEFICIARY_CODE,
            self::BENEFICIARY_ACCOUNT_NUMBER,
            self::INSTRUMENT_AMOUNT,
            self::BENEFICIARY_NAME,
            self::DRAWEE_LOCATION,
            self::PRINT_LOCATION,
            self::BENE_ADDRESS_1,
            self::BENE_ADDRESS_2,
            self::BENE_ADDRESS_3,
            self::BENE_ADDRESS_4,
            self::BENE_ADDRESS_5,
            self::INSTRUCTION_REFERENCE_NUMBER,
            self::CUSTOMER_REFERENCE_NUMBER,
            self::PAYMENT_DETAILS_1,
            self::PAYMENT_DETAILS_2,
            self::PAYMENT_DETAILS_3,
            self::PAYMENT_DETAILS_4,
            self::PAYMENT_DETAILS_5,
            self::PAYMENT_DETAILS_6,
            self::PAYMENT_DETAILS_7,
            self::CHEQUE_NUMBER,
            self::TRANSACTION_DATE,
            self::MICR_NUMBER,
            self::IFC_CODE,
            self::BENE_BANK_NAME,
            self::BENE_BANK_BRANCH_NAME,
            self::BENEFICIARY_EMAIL_ID
        ];
    }

    /**
     * Headings available in the response file
     * In order of its occurrence
     *
     * @return array
     */
    public static function getResponseFileHeadings()
    {
        return [
            self::TRANSACTION_TYPE,
            self::BENEFICIARY_CODE,
            self::BENEFICIARY_NAME,
            self::INSTRUMENT_AMOUNT,
            self::CHEQUE_NUMBER,
            self::TRANSACTION_DATE,
            self::CUSTOMER_REFERENCE_NUMBER,
            self::PAYMENT_DETAILS_1,
            self::PAYMENT_DETAILS_2,
            self::BENEFICIARY_ACCOUNT_NUMBER,
            self::BANK_REFERENCE_NO,
            self::TRANSACTION_STATUS,
            self::REJECT_REASON,
            self::IFC_CODE,
            self::MICR_NUMBER,
            self::UTR
        ];
    }
}
