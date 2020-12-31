<?php

namespace RZP\Models\Merchant\MerchantApplications;

use DB;

use RZP\Models\Base;
use RZP\Models\Base\RepositoryUpdateTestAndLive;

class Repository extends Base\Repository
{
    use RepositoryUpdateTestAndLive;

    protected $entity = 'merchant_application';

    // These are admin allowed params to search on.
    protected $appFetchParamRules = [
        Entity::MERCHANT_ID     => 'sometimes|string|size:14',
        Entity::TYPE            => 'sometimes|string',
        Entity::APPLICATION_ID  => 'sometimes|string|size:14'
    ];

    /**
     * @param string $merchantId
     * @param string $type
     *
     * @return Base\PublicCollection
     */
    public function fetchMerchantApplicationsByAppType(string $merchantId, string $type) : Base\PublicCollection
    {
        return $this->newQuery()
                    ->merchantId($merchantId)
                    ->where(Entity::TYPE, $type)
                    ->get();
    }

    /**
     * @param string $entityType
     * @param string $entityId
     *
     * @return Base\PublicCollection
     */
    public function fetchMerchantApplication(string $entityId, string $entityType) : Base\PublicCollection
    {
        return $this->newQuery()
                    ->where($entityType, $entityId)
                    ->get();
    }

    /**
     * @param array $appIds
     * @param string $type
     * @return Base\PublicCollection
     */
    public function fetchMerchantAppFromAppIdsByAppType(array $appIds, string $type) : Base\PublicCollection
    {
        return $this->newQuery()
                    ->where(Entity::TYPE, $type)
                    ->whereIn(Entity::APPLICATION_ID, $appIds)
                    ->get();
    }
}
