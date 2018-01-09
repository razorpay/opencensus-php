<?php

namespace RZP\Gateway\Fss;

class Acquirer
{
    // FSS gateway has 3 acquirer's

    const FSS   = 'fss';

    //BARB is the bank code of Bank of Baroda
    const BOB   = 'barb';

    //ICIC is the bank code of ICICI Bank
    const ICICI = 'icic';

    public static $validGatewayAcquirers = [
        self::FSS,
        self::BOB,
    ];
}
