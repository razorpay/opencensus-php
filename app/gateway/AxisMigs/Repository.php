<?php

namespace Gateway\AxisMigs;

use EE\Exception;
use Gateway\Atom;
use Models\Base;

class Repository extends Base\Repository
{
    use Base\RepositoryFetch;

    protected $entity = 'Axis';

    public function findByToken($token)
    {
        $repo = $this->repo;

        return $repo::where('token', '=', $token)
                    ->first();
    }

    public function findByMerchantTxnRef($merchantTxnRef)
    {
        $repo = $this->repo;

        return $repo::where('vpc_MerchTxnRef', '=', $merchantTxnRef)
                    ->firstOrFail();
    }
}