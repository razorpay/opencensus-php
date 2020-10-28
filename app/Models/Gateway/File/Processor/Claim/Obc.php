<?php

namespace RZP\Models\Gateway\File\Processor\Claim;

use RZP\Models\Payment;

class Obc extends NetbankingBase
{
    const GATEWAY = Payment\Gateway::NETBANKING_OBC;

    public function createFile($data)
    {
        return;
    }
}
