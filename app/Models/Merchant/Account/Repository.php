<?php

namespace RZP\Models\Merchant\Account;

use RZP\Base\Fetch;
use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Constants\Table;
use RZP\Models\Merchant\Detail;
use RZP\Models\Merchant\AccessMap;

class Repository extends Merchant\Repository
{
    protected $entity = 'account';

    protected $entityFetchParamRules = [
        Entity::PARENT_ID => 'sometimes|string|size:14',
    ];

    public function getAccounts(string $parentId, array $input): Base\PublicCollection
    {
        $skip  = 0;

        // Send all the linked accounts. Dashboard applies a local filter.
        $count = 1000;

        if (isset($input[Fetch::SKIP]) === true)
        {
            $skip = $input[Fetch::SKIP];

            unset($input[Fetch::SKIP]);
        }

        if (isset($input[Fetch::COUNT]) === true)
        {
            $count = $input[Fetch::COUNT];

            unset($input[Fetch::COUNT]);
        }

        $query = $this->newQuery()
                      ->whereNull(Entity::SUSPENDED_AT)
                      ->where(Entity::PARENT_ID, $parentId);

        foreach ($input as $attribute => $value)
        {
            $query = $query->where($attribute, $value);
        }

        return $query->take($count)
                     ->skip($skip)
                     ->get();
    }

    /**
     * @param string $applicationId
     *
     * @return Base\PublicCollection
     */
    public function fetchSubmerchantsByPartnerAppId(string $applicationId): Base\PublicCollection
    {
        $accessMapCreatedAt = $this->repo->merchant_access_map->dbColumn(AccessMap\Entity::CREATED_AT);

        $query = $this->buildQueryToFetchSubmerchants($applicationId)
                      ->orderBy($accessMapCreatedAt, 'desc')
                      ->get();

        return $query;
    }

    /**
     * @param string $submerchantId
     * @param string $applicationId
     *
     * @return Entity
     */
    public function findSubmerchantByIdAndPartnerAppId(
        string $submerchantId,
        string $applicationId): Entity
    {
        $accessMapsMerchantId = $this->repo->merchant_access_map->dbColumn(AccessMap\Entity::MERCHANT_ID);

        $query = $this->buildQueryToFetchSubmerchants($applicationId)
                      ->where($accessMapsMerchantId, $submerchantId)
                      ->firstOrFail();

        return $query;
    }

    /**
     * @param string $applicationId
     *
     * @return Base\BuilderEx
     */
    protected function buildQueryToFetchSubmerchants(string $applicationId)
    {
        $merchantDetailRepo = $this->repo->merchant_detail;

        $accessMapRepo = $this->repo->merchant_access_map;

        $merchantDetailColumns = $merchantDetailRepo->dbColumn('*');

        $merchantsMerchantId = $this->dbColumn(Entity::ID);

        $merchantDetailsMerchantId = $merchantDetailRepo->dbColumn(Detail\Entity::MERCHANT_ID);

        $accessMapsEntityType = $accessMapRepo->dbColumn(AccessMap\Entity::ENTITY_TYPE);

        $accessMapsEntityId = $accessMapRepo->dbColumn(AccessMap\Entity::ENTITY_ID);

        $accessMapsMerchantId = $accessMapRepo->dbColumn(AccessMap\Entity::MERCHANT_ID);

        $attributes = [$merchantDetailColumns, $this->dbColumn('*')];

        $query = $this->newQuery()
                      ->with(['users'])
                      ->select($attributes)
                      ->join(Table::MERCHANT_ACCESS_MAP, $merchantsMerchantId, $accessMapsMerchantId)
                      ->leftJoin(Table::MERCHANT_DETAIL, $merchantsMerchantId, $merchantDetailsMerchantId)
                      ->where($accessMapsEntityType, AccessMap\Entity::APPLICATION)
                      ->where($accessMapsEntityId, $applicationId);

        return $query;
    }
}
