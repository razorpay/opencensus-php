<?php

namespace RZP\Gateway\Mozart\NetbankingScb;

class ReconFields
{
    const BANK_TRANSACTION_ID           = 'bank_transaction_id';
    const PAYMENT_ID                    = 'payment_id';
    const BANK_PAYMENT_ID               = 'bank_payment_id';
    const NORTHAKROSS_TRANSACTION_ID    = 'northakross_transaction_id';
    const AMOUNT                        = 'amount';
    const DATE                          = 'date' ;

    const RECON_FIELDS = [
        self::BANK_TRANSACTION_ID,
        self::PAYMENT_ID,
        self::BANK_PAYMENT_ID,
        self::NORTHAKROSS_TRANSACTION_ID,
        self::AMOUNT,
        self::DATE
    ];
}
