<?php

namespace RZP\Models\P2p\Vpa;

use RZP\Exception;
use RZP\Models\P2p\Base;

class Repository extends Base\Repository
{
    protected $entity = 'p2p_vpa';

    /**
     * @param string $username
     * @return Entity
     */
    public function fetchByUsername(string $username)
    {
        return $this->newP2pQuery()
                    ->where(Entity::USERNAME, $username)
                    ->first();
    }
}
