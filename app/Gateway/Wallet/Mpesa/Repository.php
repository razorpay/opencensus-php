<?php

namespace RZP\Gateway\Wallet\Mpesa;

use App;
use RZP\Gateway\Base;
use RZP\Gateway\Wallet\Base as Wallet;

class Repository extends Base\Repository
{
    protected $repo;

    public function __construct(Wallet\Repository $repo)
    {
        $this->repo = $repo;
    }

    public function findByPaymentIdAndActions(string $paymentId, array $actions)
    {
        return $this->repo->newQuery()
                          ->where(Base\Entity::PAYMENT_ID, '=', $paymentId)
                          ->whereIn('action', $actions)
                          ->first();
    }
}
