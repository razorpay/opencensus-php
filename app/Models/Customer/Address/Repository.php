<?php

namespace RZP\Models\Customer\Address;

use RZP\Models\Base;
use RZP\Models\Customer;

class Repository extends Base\Repository
{
    use Base\RepositoryFetch;

    public function fetchCurrentPrimaryAddress($entityType, $entityId, $addressType, $currentAddressId = null)
    {
        $currentPrimaryAddresses = $this->newQuery()
                                        ->where(Entity::ENTITY_TYPE, '=', $entityType)
                                        ->where(Entity::ENTITY_ID, '=', $entityId)
                                        ->where(Entity::ADDRESS_TYPE, '=', $addressType)
                                        ->where(Entity::PRIMARY, '=', '1')
                                        ->get();

        if ($currentAddressId !== null)
        {
            return $currentPrimaryAddresses->except($currentAddressId);
        }
        else
        {
            return $currentPrimaryAddresses;
        }
    }

    // TODO: Try and make this dynamic. Something like findByIdAndEntity
    public function findByIdAndCustomer($addressId, Customer\Entity $customer)
    {
        return $this->newQuery()
                    ->where(Entity::ENTITY_TYPE, '=', Type::CUSTOMER)
                    ->where(Entity::ENTITY_ID, '=', $customer->getId())
                    ->where(Entity::ID, '=', $addressId)
                    ->firstOrFail();
    }

    public function fetchLatestAddress($entityType, $entityId, $addressType, $currentAddressId = null)
    {
        // Using get() instead of first() here because except doesn't work on an entity.
        // it works only on collection.

        $latestAddress = $this->newQuery()
                              ->where(Entity::ENTITY_TYPE, '=', $entityType)
                              ->where(Entity::ENTITY_ID, '=', $entityId)
                              ->where(Entity::ADDRESS_TYPE, '=', $addressType)
                              ->latest()
                              ->get();

        if ($currentAddressId !== null)
        {
            return $latestAddress->except($currentAddressId)->first();
        }
        else
        {
            return $latestAddress->first();
        }
    }
}