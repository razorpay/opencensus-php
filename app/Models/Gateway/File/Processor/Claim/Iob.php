<?php

namespace RZP\Models\Gateway\File\Processor\Claim;

use RZP\Models\Payment;

class Iob extends NetbankingBase
{
    const GATEWAY = Payment\Gateway::NETBANKING_IOB;

    public function createFile($data)
    {
        return;
    }
}
