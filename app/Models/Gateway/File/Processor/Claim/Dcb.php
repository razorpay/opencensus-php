<?php

namespace RZP\Models\Gateway\File\Processor\Claim;

use RZP\Models\Payment;

class Dcb extends NetbankingBase
{
    const GATEWAY = Payment\Gateway::NETBANKING_DCB;

    public function createFile($data)
    {
        return;
    }
}
