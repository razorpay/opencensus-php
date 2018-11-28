<?php

namespace RZP\Models\FundTransfer\Icici;

class Headings
{
    const PAYMENT_MODE              = 'Payment Mode';
    const BENEFICIARY_NAME          = 'Beneficiary Name';
    const BENEFICIARY_ACCOUNT_NO    = 'Beneficiary Bank A/c No';
    const BENEFICIARY_IFSC          = 'Beneficiary Bank IFSC Code';
    const AMOUNT                    = 'Instrument Amount';
    const PAYMENT_DATE              = 'Payment Date';
    const DEBIT_ACCOUNT_NO          = 'Debit Account No';
    const INSTRUMENT_REFERENCE      = 'Instrument Reference';
    const CREDIT_NARRATION          = 'Credit Narration';
    const CMS_REF_NO                = 'CMS Ref No';
    const DUMMY                     = 'Dummy';
    const DUMMY2                    = 'Dummy2';
    const DUMMY3                    = 'Dummy3';
    const BENEFICIARY_CODE          = 'Beneficiary Code';

    const REMARKS                   = 'Remarks';
    const FILE_REF_NO               = 'File Ref No';
    const PAYMENT_REF_NO            = 'Payment Ref No';
    const STATUS                    = 'Status';
    const CREATE_DATE               = 'Credit Date';

    public static function getRequestFileHeadings(): array
    {
        return [
            Headings::PAYMENT_MODE,
            Headings::BENEFICIARY_NAME,
            Headings::BENEFICIARY_ACCOUNT_NO,
            Headings::BENEFICIARY_IFSC,
            Headings::AMOUNT,
            Headings::PAYMENT_DATE,
            Headings::DEBIT_ACCOUNT_NO,
            Headings::CREDIT_NARRATION,
            Headings::INSTRUMENT_REFERENCE,
            Headings::DUMMY,
            Headings::DUMMY2,
            Headings::BENEFICIARY_CODE,
        ];
    }

    public static function getResponseFileHeadings(): array
    {
        return [
            self::FILE_REF_NO,
            self::PAYMENT_MODE,
            self::BENEFICIARY_NAME,
            self::BENEFICIARY_ACCOUNT_NO,
            self::BENEFICIARY_IFSC,
            self::AMOUNT,
            self::PAYMENT_DATE,
            self::REMARKS,
            self::CMS_REF_NO,
            self::PAYMENT_REF_NO,
            self::STATUS,
            self::CREATE_DATE,
            self::DUMMY3,
        ];
    }
}
