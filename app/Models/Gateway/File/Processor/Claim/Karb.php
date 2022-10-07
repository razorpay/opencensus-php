<?php

namespace RZP\Models\Gateway\File\Processor\Claim;

use RZP\Models\Payment;

class Karb extends NetbankingBase
{
    const GATEWAY = Payment\Gateway::NETBANKING_KARNATAKA;

    public function createFile($data)
    {
        return;
    }
}
