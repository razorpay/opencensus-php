<?php

namespace RZP\Models\P2p\Vpa;

use RZP\Exception;
use RZP\Models\P2p\Base;

class Repository extends Base\Repository
{
    protected $entity = 'p2p_vpa';

    public function findByUsernameHandle(string $username, string $handle, bool $trashed = false)
    {
        $query = $this->newQuery()
                      ->where(Entity::HANDLE, $handle)
                      ->where(Entity::USERNAME, $username);

        if ($trashed)
        {
            $query->withTrashed();
        }

        return $query->first();
    }

    public function fetchByUsernameHandle(string $username, string $handle, bool $trashed = false)
    {
        $query = $this->newP2pQuery()
                      ->where(Entity::HANDLE, $handle)
                      ->where(Entity::USERNAME, $username);

        if ($trashed)
        {
            $query->withTrashed();
        }

        return $query->first();
    }
}
