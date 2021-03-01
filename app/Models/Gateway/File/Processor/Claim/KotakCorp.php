<?php

namespace RZP\Models\Gateway\File\Processor\Claim;

use RZP\Models\Payment;

class KotakCorp extends NetbankingBase
{
    const GATEWAY = Payment\Gateway::NETBANKING_KOTAK;

    public function createFile($data)
    {
        return;
    }
}
