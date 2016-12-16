<?php

namespace RZP\Models\Upi\Vpa;

use RZP\Models\Base;
use RZP\Models\Customer\Repository as CustomerRepo;

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

    public function findByIdAndCustomerIdOrFail($vpaId, $customerId)
    {
        Entity::verifyIdAndStripSign($vpaId);

        return $this->newQuery()
                    ->where(Entity::ID, '=', $vpaId)
                    ->where(Entity::CUSTOMER_ID, '=', $customerId)
                    ->firstOrFail();
    }

    public function findByIdAndMerchantIdOrFail($vpaId, $merchantId)
    {
        Entity::verifyIdAndStripSign($vpaId);

        $customers = (new CustomerRepo)->fetchByMerchantId($merchantId);

        sd($customers->getIds(), $vpaId);

        return $this->newQuery()
                    ->where(Entity::ID, '=', $vpaId)
                    ->whereIn(Entity::CUSTOMER_ID, $customers->getIds())
                    ->firstOrFail();
    }
}
