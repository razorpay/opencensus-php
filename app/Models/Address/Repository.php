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

    public function fetchCurrentPrimaryAddressOfEntity(Base\Entity $entity, Entity $address)
    {
        $currentPrimaryAddresses = $this->newQuery()
                                        ->where(Entity::ENTITY_ID, '=', $entity->getId())
                                        ->where(Entity::TYPE, '=', $address->getType())
                                        ->where(Entity::PRIMARY, '=', '1')
                                        ->get();

        return $currentPrimaryAddresses;
    }

    public function findByEntityAndId($addressId, Base\Entity $entity)
    {
        return $this->newQuery()
                    ->where(Entity::ENTITY_ID, '=', $entity->getId())
                    ->findOrFail($addressId);
    }

    /**
     * Gets the latest address. If $except parameter is sent as true,
     * we exclude that address while fetching the latest address.
     *
     * @param Base\Entity $entity
     * @param Entity $address
     * @param null|boolean $except
     * @return Entity
     */
    public function fetchLatestAddressForEntity(Base\Entity $entity, Entity $address, $except = false)
    {
        // NOTE: except works on a collection and not on an entity.

        $latestAddress = $this->newQuery()
                              ->where(Entity::ENTITY_ID, '=', $entity->getId())
                              ->where(Entity::TYPE, '=', $address->getType())
                              ->latest()
                              ->get();

        if ($except === true)
        {
            return $latestAddress->except($address->getId())->first();
        }
        else
        {
            return $latestAddress->first();
        }
    }

    public function fetchAddressesForEntity(Base\Entity $entity, array $input)
    {
        $addresses = $this->newQuery()
                          ->where(Entity::ENTITY_ID, '=', $entity->getId());

        if (empty($input[Entity::TYPE]) === false)
        {
            $type = $input[Entity::TYPE];
            $addresses = $addresses->where(Entity::TYPE, '=', $type);
        }

        return $addresses->get();
    }
}