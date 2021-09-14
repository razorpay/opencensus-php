<?php

namespace RZP\Models\Gateway\File\Processor\Claim;

use RZP\Models\Payment;

class Bdbl extends NetbankingBase
{
    const GATEWAY = Payment\Gateway::NETBANKING_BDBL;

    public function createFile($data)
    {
        return;
    }

}
