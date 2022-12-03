<?php

namespace RZP\Models\Gateway\File\Processor\Claim;

use RZP\Models\Payment;

class Equitas extends NetbankingBase
{
    const GATEWAY = Payment\Gateway::NETBANKING_EQUITAS;

    public function createFile($data)
    {
        return;
    }
}
