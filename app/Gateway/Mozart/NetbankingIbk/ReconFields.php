<?php

namespace RZP\Gateway\Mozart\NetbankingIbk;


class ReconFields
{
    const PID               = 'pid';
    const BILLER_NAME       = 'Biller Name';
    const DATE_TIME         = 'Date & Time';
    const MERCHANT_REF_NO   = 'Merchant Ref No';
    const AMOUNT            = 'Amount';
    const CURRENCY          = 'Currency';
    const CUSTOMER_NO       = 'Customer_no';
    const DATE_BANK         = 'Date_bank';
    const BANK_REF_NO       = 'bank_ref_no';
    const JOURNAL_NO        = 'Journal_no';
    const PAID_STATUS       = 'Paidstatus';

    const PAYMENT_SUCCESS = 'Y';

    const ReconFields = [
        self::PID,
        self::BILLER_NAME,
        self::DATE_TIME,
        self::MERCHANT_REF_NO,
        self::AMOUNT,
        self::CURRENCY,
        self::CUSTOMER_NO,
        self::DATE_BANK,
        self::BANK_REF_NO,
        self::JOURNAL_NO,
        self::PAID_STATUS,
    ];
}
