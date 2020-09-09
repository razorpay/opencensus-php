<?php

namespace RZP\Models\Gateway\File\Processor\Claim;

use RZP\Models\Payment;

class Idbi extends NetbankingBase
{
    const GATEWAY = Payment\Gateway::NETBANKING_IDBI;

    public function createFile($data)
    {
        return;
    }
}
