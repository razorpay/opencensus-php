<?php

namespace RZP\Models\Vpa;

use RZP\Models\Base;
use Rzp\Models\Merchant;
use RZP\Models\Base\PublicEntity;

class Repository extends Base\Repository
{
    protected $entity = 'vpa';

    public function findByAddress($address, bool $withTrashed = false)
    {
        $query = $this->newQuery()
                      ->address($address)
                      ->latest();

        if ($withTrashed === true)
        {
            $query = $query->withTrashed();
        }

        return $query->first();
    }

    /**
     * @param  string $address
     * @param  string $merchantId
     * @return Entity|null
     */
    public function findLatestByAddressAndMerchantId(string $address, string $merchantId)
    {
        return $this->newQueryWithConnection($this->getSlaveConnection())
                    ->address($address)
                    ->merchantId($merchantId)
                    ->latest()
                    ->first();
    }

    public function findVpaByEntityIdAndEntityType(string $entityId, $entityType)
    {
        return $this->newQueryWithConnection($this->getSlaveConnection())
                    ->where(Entity::ENTITY_ID, '=', $entityId)
                    ->where(Entity::ENTITY_TYPE, '=', $entityType)
                    ->pluck(Entity::ID)
                    ->first();
    }

    public function deleteById($vpaId, $merchantId)
    {
        $vpa = $this->findByIdAndMerchantId($vpaId, $merchantId);

        $this->repo->deleteOrFail($vpa);
    }

    public function findbyPublicIdAndMerchantAlsoWithTrash(
        string $id,
        Merchant\Entity $merchant,
        $withTrashed = true): PublicEntity
    {

        $entity = $this->getEntityClass();

        $entity::verifyIdAndStripSign($id);

        $query = $this->newQuery()
                      ->where(Entity::ID, $id)
                      ->where(Entity::MERCHANT_ID, $merchant->getId());

        if ($withTrashed === true)
        {
            $query =  $query->withTrashed();
        }

        $entity = $query->first();

        if (method_exists($entity, 'merchant') === true)
        {
            $entity->merchant()->associate($merchant);
        }

        return $entity;
    }

    public function findByAddressAndEntityTypes($address, array $entityTypes, bool $withTrashed = false)
    {
        $query = $this->newQuery()
                      ->address($address)
                      ->whereIn(Entity::ENTITY_TYPE, $entityTypes)
                      ->latest();

        if ($withTrashed === true)
        {
            $query = $query->withTrashed();
        }

        return $query->first();
    }

    public function checkIfVpaBelongsToVirtualAccount(Entity $vpa)
    {
        $type                 = $this->repo->vpa->dbColumn(Entity::ENTITY_TYPE);
        $username             = $this->repo->vpa->dbColumn(Entity::USERNAME);
        $handle               = $this->repo->vpa->dbColumn(Entity::HANDLE);

        return $this->newQuery()
                    ->where($type, '=', 'virtual_account')
                    ->where($username, '=', $vpa->getUsername())
                    ->where($handle, '=', $vpa->getHandle())
                    ->first();
    }
}
