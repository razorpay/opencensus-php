<?php

namespace RZP\Models\Gateway\File\Processor\Claim;

use RZP\Models\Payment;

class Allahabad extends Base
{
    const GATEWAY = Payment\Gateway::NETBANKING_ALLAHABAD;

    public function createFile($data)
    {
        return;
    }
}