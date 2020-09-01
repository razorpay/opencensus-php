<?php

namespace RZP\Models\Gateway\File\Processor\Claim;

use RZP\Models\Payment;

class Jkb extends NetbankingBase
{
    const GATEWAY = Payment\Gateway::NETBANKING_JKB;

    public function createFile($data)
    {
        return;
    }
}
