<?php

namespace RZP\Models\Gateway\File\Processor\Claim;

use RZP\Models\Payment;

class Indusind extends Base
{
    const GATEWAY = Payment\Gateway::NETBANKING_INDUSIND;

    public function createFile()
    {
        return;
    }
}
