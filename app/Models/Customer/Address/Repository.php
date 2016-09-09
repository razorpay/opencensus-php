<?php

namespace RZP\Models\Customer\Address;

use RZP\Models\Base;

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
}