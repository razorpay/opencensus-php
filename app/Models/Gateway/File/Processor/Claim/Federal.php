<?php

namespace RZP\Models\Gateway\File\Processor\Claim;

use RZP\Models\Payment;

class Federal extends Base
{
    const GATEWAY = Payment\Gateway::NETBANKING_FEDERAL;

    public function createFile(array $data)
    {
        return;
    }
}
