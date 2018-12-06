<?php

namespace RZP\Reconciliator\NetbankingIdfc;

class Constants
{
    //Reconciliation File Header
    const RZP_PAYMENT_ID        = 'billdeskreferencenumber';
    const BANK_REFERENCE_NO     = 'banktransactionreferenceno';
    const TRANSACTION_AMOUNT    = 'transactionamount';
    const STATUS                = 'status';
    const TRANSACTION_DATE      = 'transactiondate';

    const COLUMN_HEADERS = [
        self::RZP_PAYMENT_ID,
        self::BANK_REFERENCE_NO,
        self::TRANSACTION_AMOUNT,
        self::STATUS,
        self::TRANSACTION_DATE
    ];
}
