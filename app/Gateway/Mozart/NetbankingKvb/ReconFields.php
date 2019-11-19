<?php

namespace RZP\Gateway\Mozart\NetbankingKvb;

class ReconFields
{
    const SR_NO                 = 'Sr.No.';
    const MERCHANT_CODE         = 'fldMerchCode';
    const TRANSACTION_DATE      = 'Trans Date';
    const PAYMENT_ID            = 'fldMerchRefNbr';
    const ACCOUNT_NUMBER        = 'Account Number';
    const PAYMENT_AMOUNT        = 'Transaction Amount';
    const BANK_REFERENCE_NUMBER = 'fldBankRefNbr';

    const RECON_FIELDS = [
        self::SR_NO,
        self::MERCHANT_CODE,
        self::TRANSACTION_DATE,
        self::PAYMENT_ID,
        self::ACCOUNT_NUMBER,
        self::PAYMENT_AMOUNT,
        self::BANK_REFERENCE_NUMBER
    ];
}
