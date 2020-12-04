<?php

namespace RZP\Models\Gateway\File\Processor\Claim;

use RZP\Models\Payment;

class Ubi extends NetbankingBase
{
    const GATEWAY = Payment\Gateway::NETBANKING_UBI;

    public function createFile($data)
    {
        return;
    }

}
