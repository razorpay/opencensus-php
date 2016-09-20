<?php

namespace RZP\Models\Address;

use RZP\Models\Base;
use RZP\Models\Customer;

class Repository extends Base\Repository
{
    use Base\RepositoryFetch;

    protected $appFetchParamRules = [
        Entity::MERCHANT_ID => 'sometimes|alpha_num|size:14',
        Entity::ENTITY_ID   => 'sometimes|alpha_num|size:14',
        Entity::ENTITY_TYPE => 'sometimes|string|max:32',
        Entity::TYPE        => 'sometimes|string|max:32',
        Entity::STATE       => 'sometimes|string|max:64',
        Entity::COUNTRY     => 'sometimes|string|max:64',
    ];

    public function fetchCurrentPrimaryAddressOfEntity($entityId, $type)
    {
        $currentPrimaryAddresses = $this->newQuery()
                                        ->where(Entity::ENTITY_ID, '=', $entityId)
                                        ->where(Entity::TYPE, '=', $type)
                                        ->where(Entity::PRIMARY, '=', '1')
                                        ->get();

        return $currentPrimaryAddresses;
    }

    public function findByEntityAndId($addressId, $entityId)
    {
        return $this->newQuery()
                    ->where(Entity::ENTITY_ID, '=', $entityId)
                    ->findOrFail($addressId);
    }

    /**
     * Gets the latest address. If $exceptAddress parameter is sent,
     * we exclude that address while fetching the latest address.
     *
     * @param $entityId
     * @param $type
     * @param null $exceptAddressId
     * @return Entity
     */
    public function fetchLatestAddressForEntity($entityId, $type, $exceptAddressId = null)
    {
        // NOTE: except works on a collection and not on an entity.

        $latestAddress = $this->newQuery()
                              ->where(Entity::ENTITY_ID, '=', $entityId)
                              ->where(Entity::TYPE, '=', $type)
                              ->latest()
                              ->get();

        if ($exceptAddressId !== null)
        {
            return $latestAddress->except($exceptAddressId)->first();
        }
        else
        {
            return $latestAddress->first();
        }
    }

    public function fetchAddressesForEntity($entityId, array $input)
    {
        $addresses = $this->newQuery()
                          ->where(Entity::ENTITY_ID, '=', $entityId);

        if (empty($input[Entity::TYPE]) === false)
        {
            $type = $input[Entity::TYPE];
            $addresses = $addresses->where(Entity::TYPE, '=', $type);
        }

        return $addresses->get();
    }
}