<?php

namespace RZP\Models\Address;

use RZP\Models\Base;
use RZP\Constants\Table;
use RZP\Models\Customer;

class Repository extends Base\Repository
{
    protected $entity = 'address';

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

    public function fetchPrimaryAddressOfEntityOfType(Base\Entity $entity, $type)
    {
        $primaryAddressOfType = $this->newQuery()
                                     ->where(Entity::ENTITY_ID, '=', $entity->getId())
                                     ->where(Entity::TYPE, '=', $type)
                                     ->where(Entity::PRIMARY, '=', 1)
                                     ->first();

        return $primaryAddressOfType;
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

    public function fetchRzpAddressesFor1cc(Base\Entity $entity)
    {
        $addresses = $this->newQuery()
            ->where(Entity::ENTITY_ID, '=', $entity->getId())
            ->where(function ($query)
            {
                $query->where(Entity::SOURCE_TYPE, '=', 'bulk_upload')
                    ->orWhereNull(Entity::SOURCE_TYPE);
            });
        return $addresses->get();
    }

    public function fetchThirdPartyAddressesFor1cc(Base\Entity $entity)
    {
        $addresses = $this->newQuery()
            ->where(Entity::ENTITY_ID, '=', $entity->getId())
            ->whereIn(Entity::SOURCE_TYPE, ['thirdwatch', 'payment_pages']);

        return $addresses->get();
    }

    public function fetchRzpAddressCountFor1cc(Base\Entity $entity)
    {
        $query = $this->newQuery();
        return $query->where(Entity::ENTITY_ID, '=', $entity->getId())
            ->where(function ($query)
            {
                $query->where(Entity::SOURCE_TYPE, '=', 'bulk_upload')
                    ->orWhereNull(Entity::SOURCE_TYPE);
            })
            ->count();
    }

    public function fetchThirdPartyAddressCountFor1cc(Base\Entity $entity)
    {
        $sourceTypes = ['thirdwatch', 'payment_pages'];

        return $this->newQuery()
            ->where(Entity::ENTITY_ID, '=', $entity->getId())
            ->whereIn(Entity::SOURCE_TYPE, $sourceTypes)
            ->count();
    }

    /**
     * @param string      $id
     * @param Base\Entity $entity
     * @param string|null $type
     *
     * @return Entity
     */
    public function findByPublicIdEntityAndTypeOrFail(
        string $id,
        Base\Entity $entity,
        string $type = null): Entity
    {
        Entity::verifyIdAndStripSign($id);

        $query = $this->newQuery()
                      ->where(Entity::ID, $id)
                      ->where(Entity::ENTITY_ID, $entity->getId())
                      ->where(Entity::ENTITY_TYPE, $entity->getEntity());

        if ($type !== null)
        {
            $query->where(Entity::TYPE, $type);
        }

        return $query->firstOrFailPublic();
    }

    public function fetchAddressesForContact(string $contact)
    {
        $contactCol = $this->dbColumn(Entity::CONTACT);

        return $this->newQuery()
                    ->selectRaw(Table::ADDRESS . '.*')
                    ->where($contactCol,$contact)
                    ->get();
    }

}
