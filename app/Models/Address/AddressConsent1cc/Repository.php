<?php

namespace RZP\Models\Address\AddressConsent1cc;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'address_consent_1cc';

    public function findByCustomerId($customerId)
    {
        return $this->newQueryWithConnection($this->getMasterReplicaConnection())
            ->where(Entity::CUSTOMER_ID, '=', $customerId)
            ->first();
    }

    public function getCountByCustomerId($customerId)
    {
        return $this->newQueryWithConnection($this->getMasterReplicaConnection())
            ->where(Entity::CUSTOMER_ID, '=', $customerId)
            ->where(Base\Entity::DELETED_AT, '=', null)
            ->count();
    }
}
