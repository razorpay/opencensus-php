<?php

namespace RZP\Models\Gateway\File\Processor\Claim;

use RZP\Models\Payment;

class Csb extends NetbankingBase
{
    const GATEWAY = Payment\Gateway::NETBANKING_CSB;

    public function createFile($data)
    {
        return;
    }
}
