<?php

namespace Models\Settlement;

use EE\Exception;
use Models\Base;
use Models\Card;
use Models\Transaction;
use Models\Payment;
use Trace\Trace;
use Trace\TraceCode;

class Core extends Base\Core
{
    public function retrieveById($id)
    {
        Settlement\Entity::verifyIdAndStripSign($id);

        $payment = $this->paymentRepo->findOrFail($id);

        return $payment;
    }

    public function failSettlement($setl, $reason)
    {
        ;
    }
}