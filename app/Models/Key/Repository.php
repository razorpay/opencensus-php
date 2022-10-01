<?php

namespace RZP\Models\Key;

use RZP\Models\Base;
use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestException;
use RZP\Models\Base\QueryCache\CacheQueries;
use RZP\Models\Pricing;

class Repository extends Base\Repository
{
    use CacheQueries;

    protected $entity = 'key';

    protected $appFetchParamRules = [
        Entity::MERCHANT_ID => 'sometimes|alpha_num',
    ];

    public function find($id, $columns = ['*'], string $connectionType = null)
    {
        $cacheTtl = $this->getCacheTtl();

        $prefix = Pricing\Repository::getQueryCachePrefixForDistributingLoad();

        $query = (empty($connectionType) === true) ?
            $this->newQuery() : $this->newQueryWithConnection($this->getConnectionFromType($connectionType));

        return $query
                    ->remember($cacheTtl)
                    ->cacheTags($prefix . '_' . $this->entity . '_'. $id)
                    ->find($id, $columns);
    }

    public function getKeysForMerchant($merchantId, $expired = false)
    {
        $query = $this->newQuery()->merchantId($merchantId);

        if ($expired === false)
        {
            $query->notExpired();
        }

        return $query->get();
    }

    public function getFirstActiveKeyForMerchant(string $merchantId)
    {
        return $this->newQuery()
                    ->merchantId($merchantId)
                    ->notExpired()
                    ->first();
    }

    /**
     * Get the active key entities for the given list of merchant ids.
     *
     * @param array $merchantIds
     *
     * @return Base\PublicCollection|null
     */
    public function getActiveKeysForMerchants(array $merchantIds): ?Base\PublicCollection
    {
        return $this->newQuery()
            ->whereIn(Entity::MERCHANT_ID, $merchantIds)
            ->notExpired()
            ->get();
    }

    /**
     * @param  string      $merchantId
     * @return Entity|null
     */
    public function getLatestActiveKeyForMerchant(string $merchantId)
    {
        return $this->newQuery()
                    ->merchantId($merchantId)
                    ->notExpired()
                    ->latest()
                    ->first();
    }

    public function findNotExpired($keyId)
    {
        return $this->newQuery()->notExpired()->find($keyId);
    }

    public function findByMerchantIdAndKeyId($merchantId, $keyId)
    {
        return $this->newQuery()
                    ->where(Entity::MERCHANT_ID, '=', $merchantId)
                    ->where(Entity::ID, '=', $keyId)
                    ->first();
    }
}
