<?php

namespace RZP\Gateway\Fss;

use RZP\Models\Bank\IFSC;

class Acquirer
{
    // FSS gateway has 3 acquirer's

    const FSS   = 'FSS';

    //BARB is the bank code of Bank of Baroda
    const BOB   = IFSC::BARB;

    //ICIC is the bank code of ICICI Bank
    const ICICI = IFSC::ICIC;

    public static $validGatewayAcquirers = [
        self::FSS,
        self::BOB,
    ];
}