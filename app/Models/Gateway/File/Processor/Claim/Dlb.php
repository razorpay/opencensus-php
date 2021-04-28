<?php

namespace RZP\Models\Gateway\File\Processor\Claim;

use RZP\Models\Payment;

class Dlb extends NetbankingBase
{
    const GATEWAY = Payment\Gateway::NETBANKING_DLB;

    public function createFile($data)
    {
        return;
    }
}
