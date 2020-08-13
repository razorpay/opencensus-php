<?php

namespace RZP\Models\Gateway\File\Processor\Claim;

use RZP\Models\Payment;

class Sib extends NetbankingBase
{
    const GATEWAY = Payment\Gateway::NETBANKING_SIB;

    public function createFile($data)
    {
        return;
    }
}
