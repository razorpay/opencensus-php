<?php

namespace RZP\Models\Vpa;

use RZP\Models\Base;
use RZP\Models\Customer\Repository as CustomerRepo;

class Repository extends Base\Repository
{
    protected $entity = 'vpa';

    public function findByAddress($address)
    {
        list($username, $handle) = explode(Entity::AROBASE, $address);

        return $this->newQuery()
                    ->where(Entity::USERNAME, '=', $username)
                    ->where(Entity::HANDLE, '=', $handle)
                    ->first();
    }
}
