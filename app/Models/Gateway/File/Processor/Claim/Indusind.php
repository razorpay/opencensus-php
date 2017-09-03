<?php

namespace RZP\Models\Gateway\File\Processor\Claim;

use RZP\Models\Payment;
use RZP\Models\Gateway\File\Processor;

class Indusind extends Processor\Base
{
    use GenerateClaimFile;

    const GATEWAY = Payment\Gateway::NETBANKING_INDUSIND;

    public function sendMail()
    {
        ;
    }

    public function createFile()
    {
        ;
    }
}
