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
    public function fetchByUsername(string $username, bool $trashed = false)
    {
        $query = $this->newQuery()
                      ->where(Entity::HANDLE, $this->context()->handleCode())
                      ->where(Entity::USERNAME, $username);

        if ($trashed)
        {
            $query->withTrashed();
        }

        return $query->first();
    }
}
