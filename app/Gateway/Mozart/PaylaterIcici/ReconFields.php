<?php

namespace RZP\Gateway\Mozart\PaylaterIcici;

class ReconFields
{
    const ITC                   = 'ITC';
    const PAYMENT_ID            = 'PRN';
    const BANK_PAYMENT_ID       = 'BID';
    const AMOUNT                = 'Amount';
    const DATE                  = 'date' ;

    const RECON_FIELDS = [
        self::ITC,
        self::PAYMENT_ID,
        self::BANK_PAYMENT_ID,
        self::AMOUNT,
        self::DATE
    ];
}
