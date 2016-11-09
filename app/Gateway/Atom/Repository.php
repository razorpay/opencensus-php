<?php

namespace RZP\Gateway\Atom;

use RZP\Exception;
use RZP\Gateway\Atom;
use RZP\Gateway\Base;

class Repository extends Base\Repository
{
    protected $entity = 'atom';

    public function findByToken($token)
    {
        return $this->newQuery()
                    ->where('token', '=', $token)
                    ->first();
    }

    public function findByGatewayPaymentId($gatewayTxnId)
    {
        return $this->newQuery()
                    ->where('gateway_payment_id', '=', $gatewayTxnId)
                    ->first();
    }
}