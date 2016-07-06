<?php

namespace RZP\Gateway\Atom;

use RZP\Exception;
use RZP\Gateway\Atom;
use RZP\Gateway\Base;

class Repository extends Base\Repository
{
    protected $entity = 'Atom';

    public function findByToken($token)
    {
        $repo = $this->repo;

        return $repo::where('token', '=', $token)
                    ->first();
    }

    public function findByGatewayPaymentId($gatewayTxnId)
    {
        $repo = $this->repo;

        return $repo::where('gateway_payment_id', '=', $gatewayTxnId)
                    ->first();
    }
}