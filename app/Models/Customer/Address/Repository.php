<?php

namespace RZP\Models\Customer\Address;

use RZP\Models\Base;
use RZP\Models\Customer;

class Repository extends Base\Repository
{
    use Base\RepositoryFetch;

    protected $appFetchParamRules = [
        Entity::MERCHANT_ID     => 'sometimes|alpha_num|size:14',
        Entity::ENTITY_ID       => 'sometimes|alpha_num|size:14',
        Entity::ENTITY_TYPE     => 'sometimes|string|max:64',
        Entity::ADDRESS_TYPE    => 'sometimes|string|max:64',
        Entity::STATE           => 'sometimes|string|max:64',
        Entity::COUNTRY         => 'sometimes|string|max:64',
    ];

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

    public function findByIdAndCustomer($addressId, Customer\Entity $customer)
    {
        return $this->newQuery()
                    ->where(Entity::ENTITY_TYPE, '=', Type::CUSTOMER)
                    ->where(Entity::ENTITY_ID, '=', $customer->getId())
                    ->where(Entity::ID, '=', $addressId)
                    ->firstOrFail();
    }

    public function findByEntityTypeAndId($addressId, $entityType, $entityId)
    {
        return $this->newQuery()
                    ->where(Entity::ENTITY_TYPE, '=', $entityType)
                    ->where(Entity::ENTITY_ID, '=', $entityId)
                    ->where(Entity::ID, '=', $addressId)
                    ->firstOrFail();
    }

    public function fetchLatestAddress($entityType, $entityId, $addressType, $currentAddressId = null)
    {
        // NOTE: except works on a collection and not on an entity.

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

    public function fetchAddressesForEntity($entityType, $entityId, array $input)
    {
        $addresses = $this->newQuery()
                          ->where(Entity::ENTITY_TYPE, '=', $entityType)
                          ->where(Entity::ENTITY_ID, '=', $entityId);

        if (empty($input[Entity::ADDRESS_TYPE]) === false)
        {
            $addressType = $input[Entity::ADDRESS_TYPE];
            $addresses = $addresses->where(Entity::ADDRESS_TYPE, '=', $addressType);
        }

        return $addresses->get();
    }
}