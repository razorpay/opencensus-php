<?php

namespace Models\Payment\Refund;

use Models\Base;
use Models\Payment;
use Models\Payment\Refund;
use Trace\Trace;
use Trace\TraceCode;

class Service extends Base\Service
{
    public function fetch($id)
    {
        Refund\Entity::verifyIdAndStripSign($id);

        $refund = (new Refund\Repository)->findByIdAndMerchantId($id, $this->merchant->getKey());

        return $refund->toArrayPublic();
    }

    public function fetchMultiple($input)
    {
        $refunds = (new Refund\Repository)->fetch($input, $this->merchant->getKey());

        return $refunds->toArrayPublic();
    }
}