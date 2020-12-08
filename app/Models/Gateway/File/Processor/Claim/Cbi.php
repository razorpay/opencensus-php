<?php

namespace RZP\Models\Gateway\File\Processor\Claim;

use RZP\Models\Payment;

class Cbi extends NetbankingBase
{
    const GATEWAY = Payment\Gateway::NETBANKING_CBI;

    public function createFile($data)
    {
        return;
    }
}
