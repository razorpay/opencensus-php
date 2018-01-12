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
    const BENEFICIARY_CODE          = 'Beneficiary Code';

    const PAYMENT_REF_NO            = 'Payment Ref No';
    const STATUS                    = 'Status';
    const DATE                      = 'Date';

    public static function getResponseFileHeadings(): array
    {
        return [
            'File Ref No',
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
            self::DATE,
        ];
    }
}