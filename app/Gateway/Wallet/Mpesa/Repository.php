<?php

namespace RZP\Gateway\Wallet\Mpesa;

use RZP\Gateway\Base;
use RZP\Gateway\Wallet\Base as Wallet;

class Repository extends Wallet\Repository
{
    public function findByPaymentIdAndActions(string $paymentId, array $actions)
    {
        return $this->newQuery()
                    ->where(Base\Entity::PAYMENT_ID, '=', $paymentId)
                    ->whereIn('action', $actions)
                    ->first();
    }
}
