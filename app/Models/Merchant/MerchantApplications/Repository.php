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
        Entity::TYPE            => 'sometimes|string|size:14',
        Entity::APPLICATION_ID  => 'sometimes|string|size:14'
    ];

    /**
     * @param string $merchantId
     * @param string $applicationId
     * @return mixed
     */
    public function findMerchantApplicationByID(string $merchantId, string $applicationId)
    {
        return $this->newQuery()
                    ->merchantId($merchantId)
                    ->where(Entity::APPLICATION_ID, $applicationId)
                    ->first();
    }

    /**
     * @param string $merchantId
     * @param string $type
     *
     * @return mixed
     */
    public function fetchMerchantApplicationsByType(string $merchantId, string $type) : Base\PublicCollection
    {
        return $this->newQuery()
                    ->merchantId($merchantId)
                    ->where(Entity::TYPE, $type)
                    ->get();
    }
}
