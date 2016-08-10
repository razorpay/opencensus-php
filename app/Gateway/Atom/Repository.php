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
        return $this->newquery()
                    ->where('token', '=', $token)
                    ->first();
    }

    public function findByGatewayPaymentId($gatewayTxnId)
    {
        return $this->newquery()
                    ->where('gateway_payment_id', '=', $gatewayTxnId)
                    ->first();
    }
}