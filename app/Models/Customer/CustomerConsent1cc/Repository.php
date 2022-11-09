<?php

namespace RZP\Models\Customer\CustomerConsent1cc;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'customer_consent_1cc';

    public function findByCustomerIdAndMerchantId($contact, $merchantId, bool $withReplicaConnection = true)
    {
        if($withReplicaConnection === true)
        {
            $query = $this->newQueryWithConnection($this->getMasterReplicaConnection());
        }
        else
        {
            $query = $this->newQuery();
        }

        return $query->where(Entity::CONTACT, '=', $contact)
            ->where(Entity::MERCHANT_ID, '=', $merchantId)
            ->where(Base\Entity::DELETED_AT, '=', null)
            ->first();
    }

}
