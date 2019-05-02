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
    public function fetchByUsernameHandle(string $username, string $handle, bool $trashed = false)
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
}
