<?php

namespace RZP\Models\Gateway\File\Processor\Claim;

use RZP\Models\Payment;

class Svc extends NetbankingBase
{
    const GATEWAY = Payment\Gateway::NETBANKING_SVC;

    public function createFile($data)
    {
        return;
    }
}
