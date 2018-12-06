<?php

namespace RZP\Models\Gateway\File\Processor\Claim;

use RZP\Models\Payment;

class Corporation extends Base
{
    const GATEWAY = Payment\Gateway::NETBANKING_CORPORATION;

    public function createFile($data)
    {
        return;
    }
}
