<?php

namespace RZP\Models\QrCode\Upi;

use RZP\Models\Base;
use RZP\Models\Payment;
use RZP\Models\VirtualAccount;

/**
 * This is a pseudo entity, which is only used to virtual account
 * Class Entity
 */
class Entity extends Base\PublicEntity
{
    public function getMethod()
    {
        return Payment\Method::UPI;
    }
}
