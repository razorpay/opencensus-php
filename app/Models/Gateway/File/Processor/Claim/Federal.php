<?php

namespace RZP\Models\Gateway\File\Processor\Claim;

use RZP\Models\Payment;
use RZP\Models\Gateway\File\Processor;

class Federal extends Processor\Base
{
    use GenerateClaimFile;

    const GATEWAY = Payment\Gateway::NETBANKING_FEDERAL;

    public function createFile()
    {
        ;
    }
}
