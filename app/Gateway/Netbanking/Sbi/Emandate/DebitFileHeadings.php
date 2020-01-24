<?php

namespace RZP\Gateway\Netbanking\Sbi\Emandate;

class DebitFileHeadings
{
    // Request file headings
    const SERIAL_NUMBER             = 'Serial Number';
    const UMRN                      = 'Mandate No(UMRN)';
    const CORPORATE_CODE            = 'Corporate Code';
    const CORPORATE_NAME            = 'Corporate Name';
    const MANDATE_HOLDER_NAME       = "Mandate Holder’s Name";
    const DEBIT_ACC_NO              = 'Debit Account No';
    const DEBIT_DATE                = 'Debit Date';
    const AMOUNT                    = 'Amount';
    const CUSTOMER_REF_NO           = 'Customer Ref No';
    const MANDATE_HOLDER_ACCOUNT_NO = "Mandate Holder’s Account No";
    const CREDIT_DATE               = 'Credit Date';

    const DEBIT_FILE_HEADERS = [
        self::SERIAL_NUMBER,
        self::UMRN,
        self::CORPORATE_CODE,
        self::CORPORATE_NAME,
        self::MANDATE_HOLDER_NAME,
        self::DEBIT_ACC_NO,
        self::DEBIT_DATE,
        self::AMOUNT,
        self::CUSTOMER_REF_NO,
    ];

    // Additional Response file headings
    const DEBIT_BANK_IFSC           = 'Debit Bank IFSC';
    const JOURNAL_NUMBER            = 'Journal No';
    const PROCESSING_DATE           = 'Processing Date';
    const DEBIT_STATUS              = 'Debit Status';
    const CREDIT_STATUS             = 'Credit Status';
    const REASON                    = 'Reason';
    const MANDATE_HOLDER_NAME_RESP  = 'MandateHolder Name';
    const DEBIT_DATE_RESP           = 'Debit  Date';
    const CUSTOMER_CODE             = 'Customer Code';
    const CUSTOMER_NAME             = 'Customer Name';
    const TRANSACTION_INPUT_CHANNEL = 'Transaction Input Channel';
    const FILE_NAME                 = 'File Name';
}
