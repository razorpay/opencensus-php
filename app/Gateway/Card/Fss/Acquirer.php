<?php

namespace RZP\Gateway\Card\Fss;

class Acquirer
{
    // FSS gateway has 3 acquirer's

    const FSS   = 'fss';

    //BARB is the bank code of Bank of Baroda
    const BOB   = 'barb';

    //ICIC is the bank code of ICICI Bank
    const ICICI = 'icic';

    // Bank code of SBI is SBIN
    const SBI   = 'sbin';

    public static $validGatewayAcquirers = [
        self::FSS,
        self::BOB,
        self::SBI,
    ];
}
