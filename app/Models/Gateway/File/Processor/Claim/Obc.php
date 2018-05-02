<?php

namespace RZP\Models\Gateway\File\Processor\Claim;

use RZP\Models\Payment;

class Obc extends Base
{
    const GATEWAY = Payment\Gateway::NETBANKING_OBC;
}
