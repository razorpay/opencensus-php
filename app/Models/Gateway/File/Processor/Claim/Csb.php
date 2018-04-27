<?php

namespace RZP\Models\Gateway\File\Processor\Claim;

use RZP\Models\Payment;

class Csb extends Base
{
    const GATEWAY = Payment\Gateway::NETBANKING_CSB;

    public function createFile($data)
    {
        return;
    }
}
