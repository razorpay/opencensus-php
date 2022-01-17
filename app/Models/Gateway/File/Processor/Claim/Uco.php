<?php

namespace RZP\Models\Gateway\File\Processor\Claim;

use RZP\Models\Payment;


class Uco extends NetbankingBase
{
    const GATEWAY = Payment\Gateway::NETBANKING_UCO;

    public function createFile($data)
    {
        return;
    }

}
