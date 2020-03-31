<?php

namespace RZP\Models\Payment\UpiMetadata;

use RZP\Models\Base;
use RZP\Models\Payment;

class Core extends Base\Core
{
    public function create(array $input, Payment\Entity $payment): Entity
    {
        $upiMetadata = (new Entity)->build($input);

        $upiMetadata->associatePayment($payment);

        return $upiMetadata;
    }
}
