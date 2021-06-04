<?php

namespace RZP\Gateway\Mozart\NetbankingNsdl;

class ClaimFields
{
    const PAYMENT_ID         = 'pgtxnid';
    const TRANSACTION_DATE   = 'origtxndate';
    const TRANSACTION_AMOUNT = 'amount';
    const BANK_REFERENCE_ID  = 'bankrefno';

    const COLUMNS = [
        self::PAYMENT_ID,
        self::TRANSACTION_AMOUNT,
        self::BANK_REFERENCE_ID,
        self::TRANSACTION_DATE,
    ];
}
