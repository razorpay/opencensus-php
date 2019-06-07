<?php

namespace RZP\Gateway\Mozart\NetbankingYesb;


class ReconFields
{
    const MERCHANT_CODE      = 'Merchant Code';
    const CLIENT_CODE        = 'Client Code';
    const PAYMENT_ID         = 'Merchant Reference';
    const TRANSACTION_DATE   = 'Transaction Date';
    const AMOUNT             = 'Amount';
    const SERVICE_CHARGES    = 'Service Charges';
    const BANK_REFERENCE_ID  = 'Bank Reference';
    const TRANSACTION_STATUS = 'Transaction Status';

    const RECON_FIELDS = [
        self::MERCHANT_CODE,
        self::CLIENT_CODE,
        self::PAYMENT_ID,
        self::TRANSACTION_DATE,
        self::AMOUNT,
        self::SERVICE_CHARGES,
        self::BANK_REFERENCE_ID,
        self::TRANSACTION_STATUS
    ];
}
