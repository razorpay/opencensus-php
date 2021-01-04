<?php

namespace RZP\Models\Gateway\File\Processor\Claim;

use RZP\Models\Payment;

class Federal extends NetbankingBase
{
    const GATEWAY = Payment\Gateway::NETBANKING_FEDERAL;

    public function createFile($data)
    {
        return;
    }
}
