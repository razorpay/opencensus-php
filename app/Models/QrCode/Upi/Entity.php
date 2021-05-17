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
    protected $payment;

    public function getMethod()
    {
        return Payment\Method::UPI;
    }

    public function getPayment()
    {
        return $this->payment;
    }

    public function setPayment($payment)
    {
        $this->payment = $payment;
    }
}
