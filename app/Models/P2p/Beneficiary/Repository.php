<?php

namespace RZP\Models\P2p\Beneficiary;

use RZP\Exception;
use RZP\Models\P2p\Base;

class Repository extends Base\Repository
{
    protected $entity = 'p2p_beneficiary';

    public function fetchByEntity(array $input)
    {
        $query = $this->newP2pQuery()
                      ->where($input);

        return $query->first();
    }
}
