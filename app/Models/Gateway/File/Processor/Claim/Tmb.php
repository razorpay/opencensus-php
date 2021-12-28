<?php

namespace RZP\Models\Gateway\File\Processor\Claim;

use RZP\Models\Payment;

class Tmb extends NetbankingBase
{
    const GATEWAY = Payment\Gateway::NETBANKING_TMB;

    public function createFile($data)
    {
        return;
    }
}
