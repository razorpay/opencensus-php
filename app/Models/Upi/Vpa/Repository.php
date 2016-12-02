<?php

namespace RZP\Models\Upi\Vpa;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'vpa';

    public function findByAddressOrFail($address)
    {
        list($username, $handle) = explode(Entity::AROBASE, $address);

        return $this->newQuery()
                    ->where(Entity::USERNAME, '=', $username)
                    ->where(Entity::HANDLE, '=', $handle)
                    ->firstOrFail();
    }

    public function findByAddress($address)
    {
        list($username, $handle) = explode(Entity::AROBASE, $address);

        return $this->newQuery()
                    ->where(Entity::USERNAME, '=', $username)
                    ->where(Entity::HANDLE, '=', $handle)
                    ->first();
    }

    public function fetchByCustomerId($customerId)
    {
        return $this->newQuery()
                    ->where(Entity::CUSTOMER_ID, '=', $customerId)
                    ->get();
    }
}
