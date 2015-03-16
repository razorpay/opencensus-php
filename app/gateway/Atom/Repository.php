<?php

namespace Gateway\Atom;

use EE\Exception;
use Gateway\Atom;
use Models\Base;

class Repository extends Base\Repository
{
    use Base\RepositoryFetch;

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