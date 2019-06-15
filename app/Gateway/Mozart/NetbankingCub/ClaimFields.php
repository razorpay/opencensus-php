<?php

namespace RZP\Gateway\Mozart\NetbankingCub;


class ClaimFields
{
    const PAYMENT_ID         = 'MerchantREFNO';
    const TRANSACTION_DATE   = 'TXN_DATE';
    // TODO is this a typo or actual value
    const TRANSACTION_AMOUNT = 'AMOUT';
    const BANK_REFERENCE_ID  = 'BANKREFNO';

    const COLUMNS = [
        self::PAYMENT_ID,
        self::TRANSACTION_AMOUNT,
        self::BANK_REFERENCE_ID,
        self::TRANSACTION_DATE,
    ];
}
