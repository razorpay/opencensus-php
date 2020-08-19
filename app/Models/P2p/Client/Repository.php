<?php

namespace RZP\Models\P2p\Client;

use RZP\Exception;
use RZP\Models\P2p\Base;

class Repository extends Base\Repository
{
    protected $entity = 'p2p_client';

    /**
     * @param string $clientId
     * @param string $clientType
     * @param string $handle
     * @return Entity
     */
    public function findByClientAndHandle(string $clientId, string $clientType, string $handle)
    {
        return $this->newQuery()
                    ->where(Entity::HANDLE, '=', $handle)
                    ->where(Entity::CLIENT_TYPE, '=', $clientType)
                    ->where(Entity::CLIENT_ID, '=', $clientId)
                    ->first();
    }
}
