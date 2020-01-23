<?php

namespace RZP\Models\PaymentsUpi\Vpa;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'payments_upi_vpa';

    public function firstByUsernameAndHandle($username, $handle)
    {
        return $this->newQuery()
            ->where(Entity::USERNAME, '=', $username)
            ->where(Entity::HANDLE, '=', $handle)
            ->first();
    }
}
