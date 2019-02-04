<?php

namespace RZP\Models\Merchant\AccessMap;

use DB;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Constants\Table;
use RZP\Models\Base\RepositoryUpdateTestAndLive;

class Repository extends Base\Repository
{
    use RepositoryUpdateTestAndLive;

    protected $entity = 'merchant_access_map';

    // These are admin allowed params to search on.
    protected $appFetchParamRules = [
        Entity::MERCHANT_ID     => 'sometimes|string|size:14',
        Entity::ENTITY_TYPE     => 'sometimes|string|max:255',
        Entity::ENTITY_ID       => 'sometimes|string|size:14',
        Entity::ENTITY_OWNER_ID => 'sometimes|string|size:14',
    ];

    /**
     * @param string $merchantId
     * @param string $entityId
     * @param string $entityType
     *
     * @return mixed
     */
    public function findMerchantAccessMapOnEntityId(string $merchantId, string $entityId, string $entityType)
    {
        return $this->newQuery()
                    ->merchantId($merchantId)
                    ->where(Entity::ENTITY_ID, $entityId)
                    ->where(Entity::ENTITY_TYPE, $entityType)
                    ->first();
    }

    /**
     * Returns the internal OAuth application linked to the non-pure-platform partner of the merchantId being sent.
     *
     * @param string $merchantId
     *
     * @return mixed
     */
    public function getPartnerApplication(string $merchantId)
    {
        $accessMapsEntityOwnerId = $this->dbColumn(Entity::ENTITY_OWNER_ID);
        $merchantsId             = $this->repo->merchant->dbColumn(Merchant\Entity::ID);

        return $this->newQuery()
                    ->merchantId($merchantId)
                    ->join(Table::MERCHANT, $accessMapsEntityOwnerId, $merchantsId)
                    ->where(Table::MERCHANT . '.' . Merchant\Entity::PARTNER_TYPE, '!=', Merchant\Constants::PURE_PLATFORM)
                    ->first();
    }

    /**
     * @param string $merchantId
     * @param string $entityType
     *
     * @return Base\PublicCollection
     */
    public function fetchMerchantAccessMapsOnEntityType(string $merchantId, string $entityType): Base\PublicCollection
    {
        return $this->newQuery()
                    ->merchantId($merchantId)
                    ->where(Entity::ENTITY_TYPE, $entityType)
                    ->get();
    }

    /**
     * @param string $entityType
     * @param string $entityId
     *
     * @return Base\PublicCollection
     */
    public function fetchMerchantAccessMapOnEntity(string $entityType, string $entityId): Base\PublicCollection
    {
        return $this->newQuery()
                    ->where(Entity::ENTITY_ID, $entityId)
                    ->where(Entity::ENTITY_TYPE, $entityType)
                    ->get();
    }

    /**
     * @param array $ids
     *
     * @return mixed
     */
    public function deleteMerchantAccessMapsByEntityIds(array $ids)
    {
        return $this->newQuery()
                    ->whereIn(Entity::ID, $ids)
                    ->delete();
    }
}
