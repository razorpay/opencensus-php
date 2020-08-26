<?php

namespace RZP\Models\Gateway\File\Processor\Claim;

use RZP\Models\Payment;

class Fsb extends NetbankingBase
{
    const GATEWAY = Payment\Gateway::NETBANKING_FSB;

    public function createFile($data)
    {
        return;
    }
}
