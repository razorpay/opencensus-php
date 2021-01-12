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
     * @param array $types
     *
     * @return Base\PublicCollection
     */
    public function fetchMerchantApplications(string $merchantId, array $types = []) : Base\PublicCollection
    {
        $query = $this->newQuery()->merchantId($merchantId);

        if (empty($types) === false)
        {
            $query->whereIn(Entity::TYPE, $types);
        }

        return $query->get();
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
}
