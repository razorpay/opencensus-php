<?php

namespace RZP\Models\Gateway\File\Processor\Claim;

use RZP\Models\Payment;

class Canara extends Base
{
    const GATEWAY = Payment\Gateway::NETBANKING_CANARA;

    public function createFile($data)
    {
        return;
    }
}
